<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\Event\UrlVisited;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Visit\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    use ProphecyTrait;

    private VisitsTracker $visitsTracker;
    private ObjectProphecy $em;
    private ObjectProphecy $eventDispatcher;
    private UrlShortenerOptions $options;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->em->transactional(Argument::any())->will(function (array $args) {
            [$callback] = $args;
            return $callback();
        });

        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->options = new UrlShortenerOptions();

        $this->visitsTracker = new VisitsTracker($this->em->reveal(), $this->eventDispatcher->reveal(), $this->options);
    }

    /**
     * @test
     * @dataProvider provideTrackingMethodNames
     */
    public function trackPersistsVisitAndDispatchesEvent(string $method, array $args): void
    {
        $persist = $this->em->persist(Argument::that(fn (Visit $visit) => $visit->setId('1')))->will(function (): void {
        });

        $this->visitsTracker->{$method}(...$args);

        $persist->shouldHaveBeenCalledOnce();
        $this->em->transactional(Argument::cetera())->shouldHaveBeenCalledOnce();
        $this->em->flush()->shouldHaveBeenCalledOnce();
        $this->eventDispatcher->dispatch(Argument::type(UrlVisited::class))->shouldHaveBeenCalled();
    }

    public function provideTrackingMethodNames(): iterable
    {
        yield 'track' => ['track', [ShortUrl::createEmpty(), Visitor::emptyInstance()]];
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit', [Visitor::emptyInstance()]];
    }

    /**
     * @test
     * @dataProvider provideOrphanTrackingMethodNames
     */
    public function orphanVisitsAreNotTrackedWhenDisabled(string $method): void
    {
        $this->options->trackOrphanVisits = false;

        $this->visitsTracker->{$method}(Visitor::emptyInstance());

        $this->eventDispatcher->dispatch(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->em->transactional(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->em->persist(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->em->flush()->shouldNotHaveBeenCalled();
    }

    public function provideOrphanTrackingMethodNames(): iterable
    {
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit'];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit'];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit'];
    }
}
