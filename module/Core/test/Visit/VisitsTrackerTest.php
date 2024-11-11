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
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Visit::class));
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with(
            $this->isInstanceOf(UrlVisited::class),
        );

        $result = $this->visitsTracker()->{$method}(...$args);

        self::assertInstanceOf(Visit::class, $result);
    }

    #[Test, DataProvider('provideTrackingMethodNames')]
    public function trackingIsSkippedCompletelyWhenDisabledFromOptions(string $method, array $args): void
    {
        $this->em->expects($this->never())->method('persist');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $result = $this->visitsTracker(new TrackingOptions(disableTracking: true))->{$method}(...$args);

        self::assertNull($result);
    }

    public static function provideTrackingMethodNames(): iterable
    {
        yield 'track' => ['track', [ShortUrl::createFake(), Visitor::empty()]];
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit', [Visitor::empty()]];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit', [Visitor::empty()]];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit', [Visitor::empty()]];
    }

    #[Test, DataProvider('provideOrphanTrackingMethodNames')]
    public function orphanVisitsAreNotTrackedWhenDisabled(string $method): void
    {
        $this->em->expects($this->never())->method('persist');
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $result = $this->visitsTracker(new TrackingOptions(trackOrphanVisits: false))->{$method}(Visitor::empty());

        self::assertNull($result);
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
