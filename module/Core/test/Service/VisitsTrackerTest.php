<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Service\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    /**
     * @var VisitsTracker
     */
    protected $visitsTracker;
    /**
     * @var ObjectProphecy
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->visitsTracker  = new VisitsTracker($this->em->reveal());
    }

    /**
     * @test
     */
    public function trackPersistsVisit()
    {
        $shortCode = '123ABC';
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(new ShortUrl(''));

        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);
        $this->em->persist(Argument::any())->shouldBeCalledTimes(1);
        $this->em->flush(Argument::type(Visit::class))->shouldBeCalledTimes(1);

        $this->visitsTracker->track($shortCode, Visitor::emptyInstance());
    }

    /**
     * @test
     */
    public function trackedIpAddressGetsObfuscated()
    {
        $shortCode = '123ABC';
        $test = $this;
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(new ShortUrl(''));

        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);
        $this->em->persist(Argument::any())->will(function ($args) use ($test) {
            /** @var Visit $visit */
            $visit = $args[0];
            $test->assertEquals('4.3.2.0', $visit->getRemoteAddr());
        })->shouldBeCalledTimes(1);
        $this->em->flush(Argument::type(Visit::class))->shouldBeCalledTimes(1);

        $this->visitsTracker->track($shortCode, new Visitor('', '', '4.3.2.1'));
    }

    /**
     * @test
     */
    public function infoReturnsVisistForCertainShortCode()
    {
        $shortCode = '123ABC';
        $shortUrl = new ShortUrl('http://domain.com/foo/bar');
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn($shortUrl);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);

        $list = [
            new Visit(new ShortUrl(''), Visitor::emptyInstance()),
            new Visit(new ShortUrl(''), Visitor::emptyInstance()),
        ];
        $repo2 = $this->prophesize(VisitRepository::class);
        $repo2->findVisitsByShortUrl($shortUrl, null)->willReturn($list);
        $this->em->getRepository(Visit::class)->willReturn($repo2->reveal())->shouldBeCalledTimes(1);

        $this->assertEquals($list, $this->visitsTracker->info($shortCode));
    }
}
