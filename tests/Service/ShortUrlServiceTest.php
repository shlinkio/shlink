<?php
namespace AcelayaTest\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\UrlShortener\Service\ShortUrlService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class ShortUrlServiceTest extends TestCase
{
    /**
     * @var ShortUrlService
     */
    protected $service;
    /**
     * @var ObjectProphecy|EntityManagerInterface
     */
    protected $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->service = new ShortUrlService($this->em->reveal());
    }

    /**
     * @test
     */
    public function listedUrlsAreReturnedFromEntityManager()
    {
        $repo = $this->prophesize(EntityRepository::class);
        $repo->findAll()->willReturn([
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
        ])->shouldBeCalledTimes(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $list = $this->service->listShortUrls();
        $this->assertCount(4, $list);
    }
}
