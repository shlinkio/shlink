<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Service\Tag\TagService;

class TagServiceTest extends TestCase
{
    /** @var TagService */
    private $service;
    /** @var ObjectProphecy */
    private $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->service = new TagService($this->em->reveal());
    }

    /** @test */
    public function listTagsDelegatesOnRepository()
    {
        $expected = [new Tag('foo'), new Tag('bar')];

        $repo = $this->prophesize(EntityRepository::class);
        $find = $repo->findBy(Argument::cetera())->willReturn($expected);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $result = $this->service->listTags();

        $this->assertEquals($expected, $result);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
    }

    /** @test */
    public function deleteTagsDelegatesOnRepository()
    {
        $repo = $this->prophesize(TagRepository::class);
        $delete = $repo->deleteByName(['foo', 'bar'])->willReturn(4);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $this->service->deleteTags(['foo', 'bar']);

        $delete->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
    }

    /** @test */
    public function createTagsPersistsEntities()
    {
        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        $persist = $this->em->persist(Argument::type(Tag::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $result = $this->service->createTags(['foo', 'bar']);

        $this->assertCount(2, $result);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $persist->shouldHaveBeenCalledTimes(2);
        $flush->shouldHaveBeenCalled();
    }

    /** @test */
    public function renameInvalidTagThrowsException()
    {
        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn(null);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $find->shouldBeCalled();
        $getRepo->shouldBeCalled();
        $this->expectException(EntityDoesNotExistException::class);

        $this->service->renameTag('foo', 'bar');
    }

    /** @test */
    public function renameValidTagChangesItsName()
    {
        $expected = new Tag('foo');

        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn($expected);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        $flush = $this->em->flush($expected)->willReturn(null);

        $tag = $this->service->renameTag('foo', 'bar');

        $this->assertSame($expected, $tag);
        $this->assertEquals('bar', (string) $tag);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $flush->shouldHaveBeenCalled();
    }
}
