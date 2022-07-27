<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\EventDispatcher\Mercure;

use Doctrine\ORM\EntityManagerInterface;
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
use Shlinkio\Shlink\Core\EventDispatcher\Mercure\NotifyVisitToMercure;
use Shlinkio\Shlink\Core\EventDispatcher\PublishingUpdatesGeneratorInterface;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitType;

class NotifyVisitToMercureTest extends TestCase
{
    use ProphecyTrait;

    private NotifyVisitToMercure $listener;
    private ObjectProphecy $helper;
    private ObjectProphecy $updatesGenerator;
    private ObjectProphecy $em;
    private ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->helper = $this->prophesize(PublishingHelperInterface::class);
        $this->updatesGenerator = $this->prophesize(PublishingUpdatesGeneratorInterface::class);
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->listener = new NotifyVisitToMercure(
            $this->helper->reveal(),
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
        );
        $buildNewOrphanVisitUpdate = $this->updatesGenerator->newOrphanVisitUpdate(Argument::type(Visit::class));
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate(Argument::type(Visit::class));
        $publish = $this->helper->publishUpdate(Argument::type(Update::class));

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldHaveBeenCalledOnce();
        $logDebug->shouldNotHaveBeenCalled();
        $buildNewShortUrlVisitUpdate->shouldNotHaveBeenCalled();
        $buildNewVisitUpdate->shouldNotHaveBeenCalled();
        $buildNewOrphanVisitUpdate->shouldNotHaveBeenCalled();
        $publish->shouldNotHaveBeenCalled();
    }

    /** @test */
    public function notificationsAreSentWhenVisitIsFound(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance());
        $update = Update::forTopicAndPayload('', []);

        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $logWarning = $this->logger->warning(Argument::cetera());
        $logDebug = $this->logger->debug(Argument::cetera());
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate($visit)->willReturn($update);
        $buildNewOrphanVisitUpdate = $this->updatesGenerator->newOrphanVisitUpdate($visit)->willReturn($update);
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate($visit)->willReturn($update);
        $publish = $this->helper->publishUpdate($update);

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldNotHaveBeenCalled();
        $logDebug->shouldNotHaveBeenCalled();
        $buildNewShortUrlVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewOrphanVisitUpdate->shouldNotHaveBeenCalled();
        $publish->shouldHaveBeenCalledTimes(2);
    }

    /** @test */
    public function debugIsLoggedWhenExceptionIsThrown(): void
    {
        $visitId = '123';
        $visit = Visit::forValidShortUrl(ShortUrl::createEmpty(), Visitor::emptyInstance());
        $update = Update::forTopicAndPayload('', []);
        $e = new RuntimeException('Error');

        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $logWarning = $this->logger->warning(Argument::cetera());
        $logDebug = $this->logger->debug('Error while trying to notify mercure hub with new visit. {e}', [
            'e' => $e,
        ]);
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate($visit)->willReturn($update);
        $buildNewOrphanVisitUpdate = $this->updatesGenerator->newOrphanVisitUpdate($visit)->willReturn($update);
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate($visit)->willReturn($update);
        $publish = $this->helper->publishUpdate($update)->willThrow($e);

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldNotHaveBeenCalled();
        $logDebug->shouldHaveBeenCalledOnce();
        $buildNewShortUrlVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewVisitUpdate->shouldHaveBeenCalledOnce();
        $buildNewOrphanVisitUpdate->shouldNotHaveBeenCalled();
        $publish->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideOrphanVisits
     */
    public function notificationsAreSentForOrphanVisits(Visit $visit): void
    {
        $visitId = '123';
        $update = Update::forTopicAndPayload('', []);

        $findVisit = $this->em->find(Visit::class, $visitId)->willReturn($visit);
        $logWarning = $this->logger->warning(Argument::cetera());
        $logDebug = $this->logger->debug(Argument::cetera());
        $buildNewShortUrlVisitUpdate = $this->updatesGenerator->newShortUrlVisitUpdate($visit)->willReturn($update);
        $buildNewOrphanVisitUpdate = $this->updatesGenerator->newOrphanVisitUpdate($visit)->willReturn($update);
        $buildNewVisitUpdate = $this->updatesGenerator->newVisitUpdate($visit)->willReturn($update);
        $publish = $this->helper->publishUpdate($update);

        ($this->listener)(new VisitLocated($visitId));

        $findVisit->shouldHaveBeenCalledOnce();
        $logWarning->shouldNotHaveBeenCalled();
        $logDebug->shouldNotHaveBeenCalled();
        $buildNewShortUrlVisitUpdate->shouldNotHaveBeenCalled();
        $buildNewVisitUpdate->shouldNotHaveBeenCalled();
        $buildNewOrphanVisitUpdate->shouldHaveBeenCalledOnce();
        $publish->shouldHaveBeenCalledOnce();
    }

    public function provideOrphanVisits(): iterable
    {
        $visitor = Visitor::emptyInstance();

        yield VisitType::REGULAR_404->value => [Visit::forRegularNotFound($visitor)];
        yield VisitType::INVALID_SHORT_URL->value => [Visit::forInvalidShortUrl($visitor)];
        yield VisitType::BASE_URL->value => [Visit::forBasePath($visitor)];
    }
}
