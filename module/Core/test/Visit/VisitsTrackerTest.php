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
use Shlinkio\Shlink\Core\Visit\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    use ProphecyTrait;

    private VisitsTracker $visitsTracker;
    private ObjectProphecy $em;
    private ObjectProphecy $eventDispatcher;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->visitsTracker = new VisitsTracker($this->em->reveal(), $this->eventDispatcher->reveal(), true);
    }

    /**
     * @test
     * @dataProvider provideTrackingMethodNames
     */
    public function trackPersistsVisitAndDispatchesEvent(string $method, array $args): void
    {
        $this->em->persist(Argument::that(fn (Visit $visit) => $visit->setId('1')))->shouldBeCalledOnce();
        $this->em->flush()->shouldBeCalledOnce();

        $this->visitsTracker->{$method}(...$args);

        $this->eventDispatcher->dispatch(Argument::type(UrlVisited::class))->shouldHaveBeenCalled();
    }

    public function provideTrackingMethodNames(): iterable
    {
        yield 'track' => ['track', [ShortUrl::createEmpty(), Visitor::emptyInstance()]];
        yield 'trackInvalidShortUrlVisit' => ['trackInvalidShortUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackBaseUrlVisit' => ['trackBaseUrlVisit', [Visitor::emptyInstance()]];
        yield 'trackRegularNotFoundVisit' => ['trackRegularNotFoundVisit', [Visitor::emptyInstance()]];
    }
}
