<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Mercure;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Common\UpdatePublishing\PublishingHelperInterface;
use Shlinkio\Shlink\Common\UpdatePublishing\Update;
use Shlinkio\Shlink\Core\EventDispatcher\Event\VisitLocated;
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyVisitToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

class NotifyVisitToMercureTest extends TestCase
{
    private NotifyVisitToMercure $listener;
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

        $this->listener = new NotifyVisitToMercure($this->helper, $this->updatesGenerator, $this->em, $this->logger);
    }

    /** @test */
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $this->em->expects($this->once())->method('find')->with(
            $this->equalTo(Visit::class),
            $this->equalTo($visitId),
        )->willReturn(null);
        $this->logger->expects($this->once())->method('warning')->with(
            $this->equalTo('Tried to notify {name} for visit with id "{visitId}", but it does not exist.'),
            $this->equalTo(['visitId' => $visitId, 'name' => 'Mercure']),
        );
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->never())->method('newShortUrlVisitUpdate');
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->never())->method('newVisitUpdate');
        $this->helper->expects($this->never())->method('publishUpdate');

        ($this->listener)(new VisitLocated($visitId));
    }

    /** @test */
    public function notificationsAreSentWhenVisitIsFound(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance());
        $update = Update::forTopicAndPayload('', []);

        $this->em->expects($this->once())->method('find')->with(
            $this->equalTo(Visit::class),
            $this->equalTo($visitId),
        )->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->once())->method('newShortUrlVisitUpdate')->with(
            $this->equalTo($visit),
        )->willReturn($update);
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newVisitUpdate')->with(
            $this->equalTo($visit),
        )->willReturn($update);
        $this->helper->expects($this->exactly(2))->method('publishUpdate')->with($this->equalTo($update));

        ($this->listener)(new VisitLocated($visitId));
    }

    /** @test */
    public function debugIsLoggedWhenExceptionIsThrown(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance());
        $update = Update::forTopicAndPayload('', []);
        $e = new RuntimeException('Error');

        $this->em->expects($this->once())->method('find')->with(
            $this->equalTo(Visit::class),
            $this->equalTo($visitId),
        )->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->once())->method('debug')->with(
            $this->equalTo('Error while trying to notify {name} with new visit. {e}'),
            $this->equalTo(['e' => $e, 'name' => 'Mercure']),
        );
        $this->updatesGenerator->expects($this->once())->method('newShortUrlVisitUpdate')->with(
            $this->equalTo($visit),
        )->willReturn($update);
        $this->updatesGenerator->expects($this->never())->method('newOrphanVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newVisitUpdate')->with(
            $this->equalTo($visit),
        )->willReturn($update);
        $this->helper->expects($this->once())->method('publishUpdate')->with(
            $this->equalTo($update),
        )->willThrowException($e);

        ($this->listener)(new VisitLocated($visitId));
    }

    /**
     * @test
     * @dataProvider provideOrphanVisits
     */
    public function notificationsAreSentForOrphanVisits(Visit $visit): void
    {
        $visitId = '123';
        $update = Update::forTopicAndPayload('', []);

        $this->em->expects($this->once())->method('find')->with(
            $this->equalTo(Visit::class),
            $this->equalTo($visitId),
        )->willReturn($visit);
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('debug');
        $this->updatesGenerator->expects($this->never())->method('newShortUrlVisitUpdate');
        $this->updatesGenerator->expects($this->once())->method('newOrphanVisitUpdate')->with(
            $this->equalTo($visit),
        )->willReturn($update);
        $this->updatesGenerator->expects($this->never())->method('newVisitUpdate');
        $this->helper->expects($this->once())->method('publishUpdate')->with($this->equalTo($update));

        ($this->listener)(new VisitLocated($visitId));
    }

    public function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield VisitType::REGULAR_404->value => [Visit::forRegularNotFound($visitor)];
        yield VisitType::INVALID_SHORT_URL->value => [Visit::forInvalidShortUrl($visitor)];
        yield VisitType::BASE_URL->value => [Visit::forBasePath($visitor)];
    }
}
