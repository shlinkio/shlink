<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyVisitToRabbitMq;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;
use Throwable;

use function count;
use function Functional\each;
use function Functional\noop;

class NotifyVisitToRabbitMqTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $helper;
    private ObjectProphecy $updatesGenerator;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        $this->helper = $this->prophesize(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->prophesize(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /** @test */
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        ($this->listener(new RabbitMqOptions(enabled: false)))(new VisitLocated('123'));

        $this->em->find(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->warning(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(null);
        $logWarning = $this->logger->warning(
            'Tried to notify {name} for visit with id "{visitId}", but it does not exist.',
            ['visitId' => $visitId, 'name' => 'RabbitMQ'],
        );

        ($this->listener())(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalledOnce();
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->helper->publishUpdate(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     * @dataProvider provideVisits
     */
    public function expectedChannelsAreNotifiedBasedOnTheVisitType(Visit $visit, array $expectedChannels): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        each($expectedChannels, function (string $method): void {
            $this->updatesGenerator->{$method}(Argument::type(Visit::class))->willReturn(
                Update::forTopicAndPayload('', []),
            )->shouldBeCalledOnce();
        });

        ($this->listener())(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $this->helper->publishUpdate(Argument::type(Update::class))->shouldHaveBeenCalledTimes(
            count($expectedChannels),
        );
        $this->logger->debug(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function provideVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield 'orphan visit' => [Visit::forBasePath($visitor), ['newOrphanVisitUpdate']];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(
                ShortUrl::fromMeta(ShortUrlMeta::fromRawData([
                    'longUrl' => 'foo',
                    'customSlug' => 'bar',
                ])),
                $visitor,
            ),
            ['newShortUrlVisitUpdate', 'newVisitUpdate'],
        ];
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(Visit::forBasePath(Visitor::emptyInstance()));
        $generateUpdate = $this->updatesGenerator->newOrphanVisitUpdate(Argument::type(Visit::class))->willReturn(
            Update::forTopicAndPayload('', []),
        );
        $publish = $this->helper->publishUpdate(Argument::cetera())->willThrow($e);

        ($this->listener())(new VisitLocated($visitId));

        $this->logger->debug(
            'Error while trying to notify {name} with new visit. {e}',
            ['e' => $e, 'name' => 'RabbitMQ'],
        )->shouldHaveBeenCalledOnce();
        $findVisit->shouldHaveBeenCalledOnce();
        $generateUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    /**
     * @test
     * @dataProvider provideLegacyPayloads
     */
    public function expectedPayloadIsPublishedDependingOnConfig(
        bool $legacy,
        Visit $visit,
        callable $assert,
        callable $setup,
    ): void {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $setup($this->updatesGenerator);

        ($this->listener(new RabbitMqOptions(true, $legacy)))(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $assert($this->helper, $this->updatesGenerator);
    }

    public function provideLegacyPayloads(): iterable
    {
        yield 'legacy non-orphan visit' => [
            true,
            $visit = Visit::forValidShortUrl(ShortUrl::withLongUrl(''), Visitor::emptyInstance()),
            function (ObjectProphecy|PublishingHelperInterface $helper) use ($visit): void {
                $helper->publishUpdate(Argument::that(function (Update $update) use ($visit): bool {
                    $payload = $update->payload;
                    Assert::assertEquals($payload, $visit->jsonSerialize());
                    Assert::assertArrayNotHasKey('visitedUrl', $payload);
                    Assert::assertArrayNotHasKey('type', $payload);
                    Assert::assertArrayNotHasKey('visit', $payload);
                    Assert::assertArrayNotHasKey('shortUrl', $payload);

                    return true;
                }));
            },
            noop(...),
        ];
        yield 'legacy orphan visit' => [
            true,
            Visit::forBasePath(Visitor::emptyInstance()),
            function (ObjectProphecy|PublishingHelperInterface $helper): void {
                $helper->publishUpdate(Argument::that(function (Update $update): bool {
                    $payload = $update->payload;
                    Assert::assertArrayHasKey('visitedUrl', $payload);
                    Assert::assertArrayHasKey('type', $payload);

                    return true;
                }));
            },
            noop(...),
        ];
        yield 'non-legacy non-orphan visit' => [
            false,
            Visit::forValidShortUrl(ShortUrl::withLongUrl(''), Visitor::emptyInstance()),
            function (ObjectProphecy|PublishingHelperInterface $helper): void {
                $helper->publishUpdate(Argument::type(Update::class))->shouldHaveBeenCalledTimes(2);
            },
            function (ObjectProphecy|PublishingUpdatesGeneratorInterface $updatesGenerator): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->newOrphanVisitUpdate(Argument::cetera())->shouldNotBeCalled();
                $updatesGenerator->newVisitUpdate(Argument::cetera())->willReturn($update)
                                                                     ->shouldBeCalledOnce();
                $updatesGenerator->newShortUrlVisitUpdate(Argument::cetera())->willReturn($update)
                                                                             ->shouldBeCalledOnce();
            },
        ];
        yield 'non-legacy orphan visit' => [
            false,
            Visit::forBasePath(Visitor::emptyInstance()),
            function (ObjectProphecy|PublishingHelperInterface $helper): void {
                $helper->publishUpdate(Argument::type(Update::class))->shouldHaveBeenCalledOnce();
            },
            function (ObjectProphecy|PublishingUpdatesGeneratorInterface $updatesGenerator): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->newOrphanVisitUpdate(Argument::cetera())->willReturn($update)
                                                                           ->shouldBeCalledOnce();
                $updatesGenerator->newVisitUpdate(Argument::cetera())->shouldNotBeCalled();
                $updatesGenerator->newShortUrlVisitUpdate(Argument::cetera())->shouldNotBeCalled();
            },
        ];
    }

    private function listener(?RabbitMqOptions $options = null): NotifyVisitToRabbitMq
    {
        return new NotifyVisitToRabbitMq(
            $this->helper->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
            new OrphanVisitDataTransformer(),
            $options ?? new RabbitMqOptions(enabled: true, legacyVisitsPublishing:  false),
        );
    }
}
