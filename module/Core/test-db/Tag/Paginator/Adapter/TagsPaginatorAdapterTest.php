<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Tag\Paginator\Adapter;

use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

use function Functional\map;

class TagsPaginatorAdapterTest extends DatabaseTestCase
{
    private TagRepository $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
    }

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function expectedListOfTagsIsReturned(
        ?string $searchTerm,
        ?string $orderBy,
        int $offset,
        int $length,
        array $expectedTags,
        int $expectedTotalCount,
    ): void {
        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        $adapter = new TagsPaginatorAdapter($this->repo, TagsParams::fromRawData([
            'searchTerm' => $searchTerm,
            'orderBy' => $orderBy,
        ]), null);

        $tagNames = map($adapter->getSlice($offset, $length), static fn (Tag $tag) => $tag->__toString());

        self::assertEquals($expectedTags, $tagNames);
        self::assertEquals($expectedTotalCount, $adapter->getNbResults());
    }

    public function provideFilters(): iterable
    {
        yield [null, null, 0, 10, ['another', 'bar', 'baz', 'foo'], 4];
        yield [null, null, 2, 10, ['baz', 'foo'], 4];
        yield [null, null, 1, 3, ['bar', 'baz', 'foo'], 4];
        yield [null, null, 3, 3, ['foo'], 4];
        yield [null, null, 0, 2, ['another', 'bar'], 4];
        yield ['ba', null, 0, 10, ['bar', 'baz'], 2];
        yield ['ba', null, 0, 1, ['bar'], 2];
        yield ['foo', null, 0, 10, ['foo'], 1];
        yield ['a', null, 0, 10, ['another', 'bar', 'baz'], 3];
        yield [null, 'tag-DESC', 0, 10, ['foo', 'baz', 'bar', 'another'], 4];
        yield [null, 'tag-ASC', 0, 10, ['another', 'bar', 'baz', 'foo'], 4];
        yield [null, 'tag-DESC', 0, 2, ['foo', 'baz'], 4];
        yield ['ba', 'tag-DESC', 0, 1, ['baz'], 2];
    }
}
