<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Service\Tag;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Exception\TagConflictException;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\Model\TagInfo;
use Shlinkio\Shlink\Core\Tag\TagService;

class TagServiceTest extends TestCase
{
    use ProphecyTrait;

    private TagService $service;
    private ObjectProphecy $em;
    private ObjectProphecy $repo;

    public function setUp(): void
    {
        $this->em = $this->prophesize(EntityManagerInterface::class);
        $this->repo = $this->prophesize(TagRepository::class);
        $this->em->getRepository(Tag::class)->willReturn($this->repo->reveal())->shouldBeCalled();

        $this->service = new TagService($this->em->reveal());
    }

    /** @test */
    public function listTagsDelegatesOnRepository(): void
    {
        $expected = [new Tag('foo'), new Tag('bar')];

        $find = $this->repo->findBy(Argument::cetera())->willReturn($expected);

        $result = $this->service->listTags();

        self::assertEquals($expected, $result);
        $find->shouldHaveBeenCalled();
    }

    /** @test */
    public function tagsInfoDelegatesOnRepository(): void
    {
        $expected = [new TagInfo(new Tag('foo'), 1, 1), new TagInfo(new Tag('bar'), 3, 10)];

        $find = $this->repo->findTagsWithInfo(null)->willReturn($expected);

        $result = $this->service->tagsInfo();

        self::assertEquals($expected, $result);
        $find->shouldHaveBeenCalled();
    }

    /** @test */
    public function deleteTagsDelegatesOnRepository(): void
    {
        $delete = $this->repo->deleteByName(['foo', 'bar'])->willReturn(4);

        $this->service->deleteTags(['foo', 'bar']);

        $delete->shouldHaveBeenCalled();
    }

    /** @test */
    public function createTagsPersistsEntities(): void
    {
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $persist = $this->em->persist(Argument::type(Tag::class))->willReturn(null);
        $flush = $this->em->flush()->willReturn(null);

        $result = $this->service->createTags(['foo', 'bar']);

        self::assertCount(2, $result);
        $find->shouldHaveBeenCalled();
        $persist->shouldHaveBeenCalledTimes(2);
        $flush->shouldHaveBeenCalled();
    }

    /** @test */
    public function renameInvalidTagThrowsException(): void
    {
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(null);

        $find->shouldBeCalled();
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

        $find = $this->repo->findOneBy(Argument::cetera())->willReturn($expected);
        $countTags = $this->repo->count(Argument::cetera())->willReturn($count);
        $flush = $this->em->flush()->willReturn(null);

        $tag = $this->service->renameTag($oldName, $newName);

        self::assertSame($expected, $tag);
        self::assertEquals($newName, (string) $tag);
        $find->shouldHaveBeenCalled();
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
        $find = $this->repo->findOneBy(Argument::cetera())->willReturn(new Tag('foo'));
        $countTags = $this->repo->count(Argument::cetera())->willReturn(1);
        $flush = $this->em->flush(Argument::any())->willReturn(null);

        $find->shouldBeCalled();
        $countTags->shouldBeCalled();
        $flush->shouldNotBeCalled();
        $this->expectException(TagConflictException::class);

        $this->service->renameTag('foo', 'bar');
    }
}
