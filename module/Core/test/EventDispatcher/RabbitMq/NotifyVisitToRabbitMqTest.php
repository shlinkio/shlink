<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyVisitToRabbitMq;
use Shlinkio\Shlink\Core\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Transformer\OrphanVisitDataTransformer;
use Throwable;

use function count;
use function Functional\each;
use function Functional\noop;

class NotifyVisitToRabbitMqTest extends TestCase
{
    private MockObject $helper;
    private MockObject $updatesGenerator;
    private MockObject $em;
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->createMock(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    /** @test */
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $this->helper->expects($this->never())->method('publishUpdate');
        $this->em->expects($this->never())->method('find');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');

        ($this->listener(new RabbitMqOptions(enabled: false)))(new VisitLocated('123'));
    }

    /** @test */
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(null);
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to notify {name} for visit with id "{visitId}", but it does not exist.',
            ['visitId' => $visitId, 'name' => 'RabbitMQ'],
        );
        $this->logger->expects($this->never())->method('debug');
        $this->helper->expects($this->never())->method('publishUpdate');

        ($this->listener())(new VisitLocated($visitId));
    }

    /**
     * @test
     * @dataProvider provideVisits
     */
    public function expectedChannelsAreNotifiedBasedOnTheVisitType(Visit $visit, array $expectedChannels): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        each($expectedChannels, function (string $method): void {
            $this->updatesGenerator->expects($this->once())->method($method)->with(
                $this->isInstanceOf(Visit::class),
            )->willReturn(Update::forTopicAndPayload('', []));
        });
        $this->helper->expects($this->exactly(count($expectedChannels)))->method('publishUpdate')->with(
            $this->isInstanceOf(Update::class),
        );
        $this->logger->expects($this->never())->method('debug');

        ($this->listener())(new VisitLocated($visitId));
    }

    public function provideVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield 'orphan visit' => [Visit::forBasePath($visitor), ['newOrphanVisitUpdate']];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(
                ShortUrl::fromMeta(ShortUrlCreation::fromRawData([
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
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(
            Visit::forBasePath(Visitor::emptyInstance()),
        );
        $this->updatesGenerator->expects($this->once())->method('newOrphanVisitUpdate')->with(
            $this->isInstanceOf(Visit::class),
        )->willReturn(Update::forTopicAndPayload('', []));
        $this->helper->expects($this->once())->method('publishUpdate')->withAnyParameters()->willThrowException($e);
        $this->logger->expects($this->once())->method('debug')->with(
            'Error while trying to notify {name} with new visit. {e}',
            ['e' => $e, 'name' => 'RabbitMQ'],
        );

        ($this->listener())(new VisitLocated($visitId));
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
        callable $setup,
        callable $expect,
    ): void {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $setup($this->updatesGenerator);
        $expect($this->helper, $this->updatesGenerator);

        ($this->listener(new RabbitMqOptions(true, $legacy)))(new VisitLocated($visitId));
    }

    public function provideLegacyPayloads(): iterable
    {
        yield 'legacy non-orphan visit' => [
            true,
            $visit = Visit::forValidShortUrl(ShortUrl::withLongUrl(''), Visitor::emptyInstance()),
            noop(...),
            function (MockObject & PublishingHelperInterface $helper) use ($visit): void {
                $helper->method('publishUpdate')->with($this->callback(function (Update $update) use ($visit): bool {
                    $payload = $update->payload;
                    Assert::assertEquals($payload, $visit->jsonSerialize());
                    Assert::assertArrayNotHasKey('visitedUrl', $payload);
                    Assert::assertArrayNotHasKey('type', $payload);
                    Assert::assertArrayNotHasKey('visit', $payload);
                    Assert::assertArrayNotHasKey('shortUrl', $payload);

                    return true;
                }));
            },
        ];
        yield 'legacy orphan visit' => [
            true,
            Visit::forBasePath(Visitor::emptyInstance()),
            noop(...),
            function (MockObject & PublishingHelperInterface $helper): void {
                $helper->method('publishUpdate')->with($this->callback(function (Update $update): bool {
                    $payload = $update->payload;
                    Assert::assertArrayHasKey('visitedUrl', $payload);
                    Assert::assertArrayHasKey('type', $payload);

                    return true;
                }));
            },
        ];
        yield 'non-legacy non-orphan visit' => [
            false,
            Visit::forValidShortUrl(ShortUrl::withLongUrl(''), Visitor::emptyInstance()),
            function (MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
                $updatesGenerator->expects($this->once())->method('newVisitUpdate')->withAnyParameters()->willReturn(
                    $update,
                );
                $updatesGenerator->expects($this->once())->method('newShortUrlVisitUpdate')->willReturn($update);
            },
            function (MockObject & PublishingHelperInterface $helper): void {
                $helper->expects($this->exactly(2))->method('publishUpdate')->with($this->isInstanceOf(Update::class));
            },
        ];
        yield 'non-legacy orphan visit' => [
            false,
            Visit::forBasePath(Visitor::emptyInstance()),
            function (MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->expects($this->once())->method('newOrphanVisitUpdate')->willReturn($update);
                $updatesGenerator->expects($this->never())->method('newVisitUpdate');
                $updatesGenerator->expects($this->never())->method('newShortUrlVisitUpdate');
            },
            function (MockObject & PublishingHelperInterface $helper): void {
                $helper->expects($this->once())->method('publishUpdate')->with($this->isInstanceOf(Update::class));
            },
        ];
    }

    private function listener(?RabbitMqOptions $options = null): NotifyVisitToRabbitMq
    {
        return new NotifyVisitToRabbitMq(
            $this->helper,
            $this->updatesGenerator,
            $this->em,
            $this->logger,
            new OrphanVisitDataTransformer(),
            $options ?? new RabbitMqOptions(enabled: true, legacyVisitsPublishing:  false),
        );
    }
}
