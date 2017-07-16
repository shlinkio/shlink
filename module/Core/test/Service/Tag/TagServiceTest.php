<?php
namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
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

    /**
     * @test
     */
    public function deleteTagsDelegatesOnRepository()
    {
        $repo = $this->prophesize(TagRepository::class);
        /** @var MethodProphecy $delete */
        $delete = $repo->deleteByName(['foo', 'bar'])->willReturn(4);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $this->service->deleteTags(['foo', 'bar']);

        $delete->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function createTagsPersistsEntities()
    {
        $repo = $this->prophesize(TagRepository::class);
        /** @var MethodProphecy $find */
        $find = $repo->findOneBy(Argument::cetera())->willReturn(new Tag());
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        /** @var MethodProphecy $persist */
        $persist = $this->em->persist(Argument::type(Tag::class))->willReturn(null);
        /** @var MethodProphecy $flush */
        $flush = $this->em->flush()->willReturn(null);

        $result = $this->service->createTags(['foo', 'bar']);

        $this->assertCount(2, $result);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $persist->shouldHaveBeenCalledTimes(2);
        $flush->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function renameInvalidTagThrowsException()
    {
        $repo = $this->prophesize(TagRepository::class);
        /** @var MethodProphecy $find */
        $find = $repo->findOneBy(Argument::cetera())->willReturn(null);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $find->shouldBeCalled();
        $getRepo->shouldBeCalled();
        $this->expectException(EntityDoesNotExistException::class);

        $this->service->renameTag('foo', 'bar');
    }

    /**
     * @test
     */
    public function renameValidTagChangesItsName()
    {
        $expected = new Tag();

        $repo = $this->prophesize(TagRepository::class);
        /** @var MethodProphecy $find */
        $find = $repo->findOneBy(Argument::cetera())->willReturn($expected);
        /** @var MethodProphecy $getRepo */
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        /** @var MethodProphecy $flush */
        $flush = $this->em->flush($expected)->willReturn(null);

        $tag = $this->service->renameTag('foo', 'bar');

        $this->assertSame($expected, $tag);
        $this->assertEquals('bar', $tag->getName());
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $flush->shouldHaveBeenCalled();
    }
}
