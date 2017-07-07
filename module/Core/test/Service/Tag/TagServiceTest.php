<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Service\Tag\TagService;
use PHPUnit\Framework\TestCase;

class TagServiceTest extends TestCase
{
    /**
     * @var TagService
     */
    private $service;
    /**
     * @var ObjectProphecy
     */
    private $em;

    public function setUp()
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->service = new TagService($this->em->reveal());
    }

    /**
     * @test
     */
    public function listTagsDelegatesOnRepository()
    {
        $expected = [new Tag(), new Tag()];

        $repo = $this->prophesize(EntityRepository::class);
        /** @var MethodProphecy $find */
        $find = $repo->findBy(Argument::cetera())->willReturn($expected);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $result = $this->service->listTags();

        $this->assertEquals($expected, $result);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
    }
}
