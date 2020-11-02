<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\NotifyVisitToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\VisitLocated;
use Shlinkio\Shlink\Core\Mercure\MercureUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\Model\Visitor;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

class NotifyVisitToMercureTest extends TestCase
{
    use ProphecyTrait;

    private NotifyVisitToMercure $listener;
    private ObjectProphecy $publisher;
    private ObjectProphecy $updatesGenerator;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->publisher = $this->prophesize(PublisherInterface::class);
        $this->updatesGenerator = $this->prophesize(MercureUpdatesGeneratorInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->listener = new NotifyVisitToMercure(
            $this->publisher->reveal(),
            $this->updatesGenerator->reveal(),
            $this->em->reveal(),
            $this->logger->reveal(),
        );
    }

    /** @test */
    public function notificationsAreNotSentWhenVisitCannotBeFound(): void
    {
        $visitId = '123';
        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn(null);
        $logWarning = $this->logger->warning(
            'Tried to notify mercure for visit with id "{visitId}", but it does not exist.',
            ['visitId' => $visitId],
        );
        $logDebug = $this->logger->debug(Argument::cetera());
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate(
            Argument::type(Visit::class),
        )->willReturn(new Update('', ''));
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate(Argument::type(Visit::class))->willReturn(
            new Update('', ''),
        );
        $publish = $this->publisher->__invoke(Argument::type(Update::class));

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalledOnce();
        $logDebug->shouldNotHaveBeenCalled();
        $buildNewShortUrlVisitUpdate->shouldNotHaveBeenCalled();
        $buildNewVisitUpdate->shouldNotHaveBeenCalled();
        $publish->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function notificationsAreSentWhenVisitIsFound(): void
    {
        $visitId = '123';
        $visit = new Visit(new ShortUrl(''), Visitor::emptyInstance());
        $update = new Update('', '');

        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $logWarning = $this->logger->warning(Argument::cetera());
        $logDebug = $this->logger->debug(Argument::cetera());
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate($visit)->willReturn($update);
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate($visit)->willReturn($update);
        $publish = $this->publisher->__invoke($update);

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldNotHaveBeenCalled();
        $logDebug->shouldNotHaveBeenCalled();
        $buildNewShortUrlVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewVisitUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledTimes(2);
    }

    /** @test */
    public function debugIsLoggedWhenExceptionIsThrown(): void
    {
        $visitId = '123';
        $visit = new Visit(new ShortUrl(''), Visitor::emptyInstance());
        $update = new Update('', '');
        $e = new RuntimeException('Error');

        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $logWarning = $this->logger->warning(Argument::cetera());
        $logDebug = $this->logger->debug('Error while trying to notify mercure hub with new visit. {e}', [
            'e' => $e,
        ]);
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate($visit)->willReturn($update);
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate($visit)->willReturn($update);
        $publish = $this->publisher->__invoke($update)->willThrow($e);

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldNotHaveBeenCalled();
        $logDebug->shouldHaveBeenCalledOnce();
        $buildNewShortUrlVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewVisitUpdate->shouldNotHaveBeenCalled();
        $publish->shouldHaveBeenCalledOnce();
    }
}
