<?php
namespace AcelayaTest\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\UrlShortener\Repository\ShortUrlRepository;
use Acelaya\UrlShortener\Service\ShortUrlService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
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
        $list = [
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
            new ShortUrl(),
        ];

        $repo = $this->prophesize(ShortUrlRepository::class);
        $repo->findList(Argument::cetera())->willReturn($list)->shouldBeCalledTimes(1);
        $repo->countList(Argument::cetera())->willReturn(count($list))->shouldBeCalledTimes(1);
        $this->em->getRepository(ShortUrl::class)->willReturn($repo->reveal());

        $list = $this->service->listShortUrls();
        $this->assertEquals(4, $list->getCurrentItemCount());
    }
}
