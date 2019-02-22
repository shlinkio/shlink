<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\IpGeolocation\Model\Location;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Exception\IpCannotBeLocatedException;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Service\VisitService;
use function array_shift;
use function count;
use function func_get_args;

class VisitServiceTest extends TestCase
{
    /** @var VisitService */
    private $visitService;
    /** @var ObjectProphecy */
    private $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->visitService = new VisitService($this->em->reveal());
    }

    /** @test */
    public function locateVisitsIteratesAndLocatesUnlocatedVisits(): void
    {
        $unlocatedVisits = [
            new Visit(new ShortUrl('foo'), Visitor::emptyInstance()),
            new Visit(new ShortUrl('bar'), Visitor::emptyInstance()),
        ];

        $repo = $this->prophesize(VisitRepository::class);
        $findUnlocatedVisits = $repo->findUnlocatedVisits()->willReturn($unlocatedVisits);
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function () {
        });
        $flush = $this->em->flush()->will(function () {
        });
        $clear = $this->em->clear()->will(function () {
        });

        $this->visitService->locateUnlocatedVisits(function () {
            return Location::emptyInstance();
        }, function () {
            $args = func_get_args();

            $this->assertInstanceOf(VisitLocation::class, array_shift($args));
            $this->assertInstanceOf(Visit::class, array_shift($args));
        });

        $findUnlocatedVisits->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
        $persist->shouldHaveBeenCalledTimes(count($unlocatedVisits));
        $flush->shouldHaveBeenCalledTimes(count($unlocatedVisits));
        $clear->shouldHaveBeenCalledTimes(count($unlocatedVisits));
    }

    /** @test */
    public function visitsWhichCannotBeLocatedAreIgnored()
    {
        $unlocatedVisits = [
            new Visit(new ShortUrl('foo'), Visitor::emptyInstance()),
        ];

        $repo = $this->prophesize(VisitRepository::class);
        $findUnlocatedVisits = $repo->findUnlocatedVisits()->willReturn($unlocatedVisits);
        $getRepo = $this->em->getRepository(Visit::class)->willReturn($repo->reveal());

        $persist = $this->em->persist(Argument::type(Visit::class))->will(function () {
        });
        $flush = $this->em->flush()->will(function () {
        });
        $clear = $this->em->clear()->will(function () {
        });

        $this->visitService->locateUnlocatedVisits(function () {
            throw new IpCannotBeLocatedException('Cannot be located');
        });

        $findUnlocatedVisits->shouldHaveBeenCalledOnce();
        $getRepo->shouldHaveBeenCalledOnce();
        $persist->shouldNotHaveBeenCalled();
        $flush->shouldNotHaveBeenCalled();
        $clear->shouldNotHaveBeenCalled();
    }
}
