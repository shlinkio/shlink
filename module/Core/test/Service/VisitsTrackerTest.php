<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Laminas\Stdlib\ArrayUtils;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\EventDispatcher\ShortUrlVisited;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Service\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    private VisitsTracker $visitsTracker;
    private ObjectProphecy $em;
    private ObjectProphecy $eventDispatcher;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->visitsTracker  = new VisitsTracker($this->em->reveal(), $this->eventDispatcher->reveal());
    }

    /** @test */
    public function trackPersistsVisit(): void
    {
        $shortCode = '123ABC';
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(new ShortUrl(''));

        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledOnce();
        $this->em->persist(Argument::that(fn (Visit $visit) => $visit->setId('1')))->shouldBeCalledOnce();
        $this->em->flush()->shouldBeCalledOnce();

        $this->visitsTracker->track($shortCode, Visitor::emptyInstance());

        $this->eventDispatcher->dispatch(Argument::type(ShortUrlVisited::class))->shouldHaveBeenCalled();
    }

    /** @test */
    public function trackedIpAddressGetsObfuscated(): void
    {
        $shortCode = '123ABC';
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(new ShortUrl(''));

        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledOnce();
        $this->em->persist(Argument::any())->will(function ($args) {
            /** @var Visit $visit */
            $visit = $args[0];
            Assert::assertEquals('4.3.2.0', $visit->getRemoteAddr());
            $visit->setId('1');
            return $visit;
        })->shouldBeCalledOnce();
        $this->em->flush()->shouldBeCalledOnce();

        $this->visitsTracker->track($shortCode, new Visitor('', '', '4.3.2.1'));

        $this->eventDispatcher->dispatch(Argument::type(ShortUrlVisited::class))->shouldHaveBeenCalled();
    }

    /** @test */
    public function infoReturnsVisitsForCertainShortCode(): void
    {
        $shortCode = '123ABC';
        $repo = $this->prophesize(EntityRepository::class);
        $count = $repo->count(['shortCode' => $shortCode])->willReturn(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledOnce();

        $list = [
            new Visit(new ShortUrl(''), Visitor::emptyInstance()),
            new Visit(new ShortUrl(''), Visitor::emptyInstance()),
        ];
        $repo2 = $this->prophesize(VisitRepository::class);
        $repo2->findVisitsByShortCode($shortCode, Argument::type(DateRange::class), 1, 0)->willReturn($list);
        $repo2->countVisitsByShortCode($shortCode, Argument::type(DateRange::class))->willReturn(1);
        $this->em->getRepository(Visit::class)->willReturn($repo2->reveal())->shouldBeCalledOnce();

        $paginator = $this->visitsTracker->info($shortCode, new VisitsParams());

        $this->assertEquals($list, ArrayUtils::iteratorToArray($paginator->getCurrentItems()));
        $count->shouldHaveBeenCalledOnce();
    }
}
