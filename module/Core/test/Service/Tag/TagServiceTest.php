<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Service\Tag\TagService;

class TagServiceTest extends TestCase
{
    private TagService $service;
    private ObjectProphecy $em;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->service = new TagService($this->em->reveal());
    }

    /** @test */
    public function listTagsDelegatesOnRepository(): void
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
    public function deleteTagsDelegatesOnRepository(): void
    {
        $repo = $this->prophesize(TagRepository::class);
        $delete = $repo->deleteByName(['foo', 'bar'])->willReturn(4);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $this->service->deleteTags(['foo', 'bar']);

        $delete->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
    }

    /** @test */
    public function createTagsPersistsEntities(): void
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
    public function renameInvalidTagThrowsException(): void
    {
        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn(null);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());

        $find->shouldBeCalled();
        $getRepo->shouldBeCalled();
        $this->expectException(TagNotFoundException::class);

        $this->service->renameTag('foo', 'bar');
    }

    /**
     * @test
     * @dataProvider provideValidRenames
     */
    public function renameValidTagChangesItsName(string $oldName, string $newName, int $count): void
    {
        $expected = new Tag('foo');

        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn($expected);
        $countTags = $repo->count(Argument::cetera())->willReturn($count);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        $flush = $this->em->flush()->willReturn(null);

        $tag = $this->service->renameTag($oldName, $newName);

        $this->assertSame($expected, $tag);
        $this->assertEquals($newName, (string) $tag);
        $find->shouldHaveBeenCalled();
        $getRepo->shouldHaveBeenCalled();
        $flush->shouldHaveBeenCalled();
        $countTags->shouldHaveBeenCalledTimes($count > 0 ? 0 : 1);
    }

    public function provideValidRenames(): iterable
    {
        yield 'same names' => ['foo', 'foo', 1];
        yield 'different names names' => ['foo', 'bar', 0];
    }

    /** @test */
    public function renameTagToAnExistingNameThrowsException(): void
    {
        $repo = $this->prophesize(TagRepository::class);
        $find = $repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $countTags = $repo->count(Argument::cetera())->willReturn(1);
        $getRepo = $this->em->getRepository(Tag::class)->willReturn($repo->reveal());
        $flush = $this->em->flush(Argument::any())->willReturn(null);

        $find->shouldBeCalled();
        $getRepo->shouldBeCalled();
        $countTags->shouldBeCalled();
        $flush->shouldNotBeCalled();
        $this->expectException(TagConflictException::class);

        $this->service->renameTag('foo', 'bar');
    }
}
