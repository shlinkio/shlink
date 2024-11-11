<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\RabbitMq;

use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Config\Options\RabbitMqOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\EventDispatcher\RabbitMq\NotifyVisitToRabbitMq;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Throwable;

use function array_walk;
use function count;

class NotifyVisitToRabbitMqTest extends TestCase
{
    private MockObject & PublishingHelperInterface $helper;
    private MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator;
    private MockObject & EntityManagerInterface $em;
    private MockObject & LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->createMock(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    #[Test]
    public function doesNothingWhenTheFeatureIsNotEnabled(): void
    {
        $this->helper->expects($this->never())->method('publishUpdate');
        $this->em->expects($this->never())->method('find');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');

        ($this->listener(new RabbitMqOptions(enabled: false)))(new VisitLocated('123'));
    }

    #[Test]
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

    #[Test, DataProvider('provideVisits')]
    public function expectedChannelsAreNotifiedBasedOnTheVisitType(Visit $visit, array $expectedChannels): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        array_walk($expectedChannels, function (string $method): void {
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

    public static function provideVisits(): iterable
    {
        $visitor = Visitor::empty();

        yield 'orphan visit' => [Visit::forBasePath($visitor), ['newOrphanVisitUpdate']];
        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(
                ShortUrl::create(ShortUrlCreation::fromRawData([
                    'longUrl' => 'https://foo',
                    'customSlug' => 'bar',
                ])),
                $visitor,
            ),
            ['newShortUrlVisitUpdate', 'newVisitUpdate'],
        ];
    }

    #[Test, DataProvider('provideExceptions')]
    public function printsDebugMessageInCaseOfError(Throwable $e): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(
            Visit::forBasePath(Visitor::empty()),
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

    public static function provideExceptions(): iterable
    {
        yield [new RuntimeException('RuntimeException Error')];
        yield [new Exception('Exception Error')];
        yield [new DomainException('DomainException Error')];
    }

    #[Test, DataProvider('providePayloads')]
    public function expectedPayloadIsPublishedDependingOnConfig(
        Visit $visit,
        callable $setup,
        callable $expect,
    ): void {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $setup($this->updatesGenerator);
        $expect($this->helper, $this->updatesGenerator);

        ($this->listener())(new VisitLocated($visitId));
    }

    public static function providePayloads(): iterable
    {
        $exactly = static fn (int $expectedCount) => new InvokedCountMatcher($expectedCount);
        $once = static fn () => $exactly(1);
        $never = static fn () => $exactly(0);

        yield 'non-orphan visit' => [
            Visit::forValidShortUrl(ShortUrl::withLongUrl('https://longUrl'), Visitor::empty()),
            function (MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator) use ($once, $never): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->expects($never())->method('newOrphanVisitUpdate');
                $updatesGenerator->expects($once())->method('newVisitUpdate')->withAnyParameters()->willReturn(
                    $update,
                );
                $updatesGenerator->expects($once())->method('newShortUrlVisitUpdate')->willReturn($update);
            },
            function (MockObject & PublishingHelperInterface $helper) use ($exactly): void {
                $helper->expects($exactly(2))->method('publishUpdate')->with(self::isInstanceOf(Update::class));
            },
        ];
        yield 'orphan visit' => [
            Visit::forBasePath(Visitor::empty()),
            function (MockObject & PublishingUpdatesGeneratorInterface $updatesGenerator) use ($once, $never): void {
                $update = Update::forTopicAndPayload('', []);
                $updatesGenerator->expects($once())->method('newOrphanVisitUpdate')->willReturn($update);
                $updatesGenerator->expects($never())->method('newVisitUpdate');
                $updatesGenerator->expects($never())->method('newShortUrlVisitUpdate');
            },
            function (MockObject & PublishingHelperInterface $helper) use ($once): void {
                $helper->expects($once())->method('publishUpdate')->with(self::isInstanceOf(Update::class));
            },
        ];
    }

    private function listener(RabbitMqOptions|null $options = null): NotifyVisitToRabbitMq
    {
        return new NotifyVisitToRabbitMq(
            $this->helper,
            $this->updatesGenerator,
            $this->em,
            $this->logger,
            $options ?? new RabbitMqOptions(enabled: true),
        );
    }
}
