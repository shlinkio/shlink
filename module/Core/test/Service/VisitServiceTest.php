<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Entity\VisitLocation;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Repository\VisitRepository;
use Shlinkio\Shlink\Core\Service\VisitService;

class VisitServiceTest extends TestCase
{
    /**
     * @var VisitService
     */
    protected $visitService;
    /**
     * @var ObjectProphecy
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManager::class);
        $this->visitService = new VisitService($this->em->reveal());
    }

    /**
     * @test
     */
    public function locateVisitPersistsProvidedVisit()
    {
        $visit = new Visit(new ShortUrl(''), Visitor::emptyInstance());
        $this->em->persist($visit)->shouldBeCalledOnce();
        $this->em->flush()->shouldBeCalledOnce();
        $this->visitService->locateVisit($visit, new VisitLocation([]));
    }

    /**
     * @test
     */
    public function getUnlocatedVisitsFallbacksToRepository()
    {
        $repo = $this->prophesize(VisitRepository::class);
        $repo->findUnlocatedVisits()->shouldBeCalledOnce();
        $this->em->getRepository(Visit::class)->willReturn($repo->reveal())->shouldBeCalledOnce();
        $this->visitService->getUnlocatedVisits();
    }
}
