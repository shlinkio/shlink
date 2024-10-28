<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    private MockObject & EntityManager $em;
    private MockObject & EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->em->method('wrapInTransaction')->willReturnCallback(fn (callable $callback) => $callback());

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    #[Test, DataProvider('provideTrackingMethodNames')]
    public function trackPersistsVisitAndDispatchesEvent(string $method, array $args): void
    {
        $this->em->expects($this->once())->method('persist')->with(
            $this->callback(fn (Visit $visit) => $visit->setId('1') !== null),
        );
        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(UrlVisited::class),
        );

        $this->visitsTracker()->{$method}(...$args);
    }

    #[Test, DataProvider('provideTrackingMethodNames')]
    public function trackingIsSkippedCompletelyWhenDisabledFromOptions(string $method, array $args): void
    {
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->visitsTracker(new TrackingOptions(disableTracking: true))->{$method}(...$args);
    }

    public static function provideTrackingMethodNames(): iterable
    {
        yield 'track' => ['track', [ShortUrl::createFake(), Visitor::emptyInstance()]];
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit', [Visitor::emptyInstance()]];
    }

    #[Test, DataProvider('provideOrphanTrackingMethodNames')]
    public function orphanVisitsAreNotTrackedWhenDisabled(string $method): void
    {
        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->visitsTracker(new TrackingOptions(trackOrphanVisits: false))->{$method}(Visitor::emptyInstance());
    }

    public static function provideOrphanTrackingMethodNames(): iterable
    {
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit'];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit'];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit'];
    }

    private function visitsTracker(TrackingOptions|null $options = null): VisitsTracker
    {
        return new VisitsTracker($this->em, $this->eventDispatcher, $options ?? new TrackingOptions());
    }
}
