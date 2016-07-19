<?php
namespace ShlinkioTest\Shlink\Core\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Service\VisitsTracker;

class VisitsTrackerTest extends TestCase
{
    /**
     * @test
     */
    public function trackPersistsVisit()
    {
        $shortCode = '123ABC';
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findOneBy(['shortCode' => $shortCode])->willReturn(new ShortUrl());

        $em = $this->prophesize(EntityManager::class);
        $em->getRepository(ShortUrl::class)->willReturn($repo->reveal())->shouldBeCalledTimes(1);
        $em->persist(Argument::any())->shouldBeCalledTimes(1);
        $em->flush()->shouldBeCalledTimes(1);

        $visitsTracker = new VisitsTracker($em->reveal());
        $visitsTracker->track($shortCode);
    }
}
