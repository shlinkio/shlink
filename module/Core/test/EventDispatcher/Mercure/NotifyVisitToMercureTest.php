<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Mercure;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\Config\Options\RealTimeUpdatesOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyVisitToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

class NotifyVisitToMercureTest extends TestCase
{
    private NotifyVisitToMercure $listener;
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

        $this->listener = new NotifyVisitToMercure(
            $this->helper,
            $this->updatesGenerator,
            $this->em,
            $this->logger,
            new RealTimeUpdatesOptions(),
        );
    }

    #[Test]
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn(null);
        $this->logger->expects($this->once())->method('warning')->with(
            'Tried to notify {name} for visit with id "{visitId}", but it does not exist.',
            ['visitId' => $visitId, 'name' => 'Mercure'],
        );
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->never())->method('newShortUrlVisitUpdate');
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->never())->method('newVisitUpdate');
        $this->helper->expects($this->never())->method('publishUpdate');

        ($this->listener)(new UrlVisited($visitId));
    }

    #[Test]
    public function notificationsAreSentWhenVisitIsFound(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty());
        $update = Update::forTopicAndPayload('', []);

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->once())->method('newShortUrlVisitUpdate')->with($visit)->willReturn(
            $update,
        );
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newVisitUpdate')->with($visit)->willReturn($update);
        $this->helper->expects($this->exactly(2))->method('publishUpdate')->with($update);

        ($this->listener)(new UrlVisited($visitId));
    }

    #[Test]
    public function debugIsLoggedWhenExceptionIsThrown(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createFake(), Visitor::empty());
        $update = Update::forTopicAndPayload('', []);
        $e = new RuntimeException('Error');

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())->method('debug')->with(
            'Error while trying to notify {name} with new visit. {e}',
            ['e' => $e, 'name' => 'Mercure'],
        );
        $this->updatesGenerator->expects($this->once())->method('newShortUrlVisitUpdate')->with($visit)->willReturn(
            $update,
        );
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newVisitUpdate')->with($visit)->willReturn($update);
        $this->helper->expects($this->once())->method('publishUpdate')->with($update)->willThrowException($e);

        ($this->listener)(new UrlVisited($visitId));
    }

    #[Test, DataProvider('provideOrphanVisits')]
    public function notificationsAreSentForOrphanVisits(Visit $visit): void
    {
        $visitId = '123';
        $update = Update::forTopicAndPayload('', []);

        $this->em->expects($this->once())->method('find')->with(Visit::class, $visitId)->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->never())->method('newShortUrlVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newOrphanVisitUpdate')->with($visit)->willReturn(
            $update,
        );
        $this->updatesGenerator->expects($this->never())->method('newVisitUpdate');
        $this->helper->expects($this->once())->method('publishUpdate')->with($update);

        ($this->listener)(new UrlVisited($visitId));
    }

    public static function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::empty();

        yield VisitType::REGULAR_404->value => [Visit::forRegularNotFound($visitor)];
        yield VisitType::INVALID_SHORT_URL->value => [Visit::forInvalidShortUrl($visitor)];
        yield VisitType::BASE_URL->value => [Visit::forBasePath($visitor)];
    }
}
