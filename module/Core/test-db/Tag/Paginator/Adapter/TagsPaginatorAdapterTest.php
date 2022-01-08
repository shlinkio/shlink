<?php

declare(strict_types=1);

namespace ShlinkioDbTest\Shlink\Core\Tag\Paginator\Adapter;

use Shlinkio\Shlink\Core\Entity\Tag;
use Shlinkio\Shlink\Core\Repository\TagRepository;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;
use Shlinkio\Shlink\TestUtils\DbTest\DatabaseTestCase;

class TagsPaginatorAdapterTest extends DatabaseTestCase
{
    private TagRepository $repo;

    protected function beforeEach(): void
    {
        $this->repo = $this->getEntityManager()->getRepository(Tag::class);
    }

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function expectedListOfTagsIsReturned(
        ?string $searchTerm,
        int $offset,
        int $length,
        int $expectedSliceSize,
        int $expectedTotalCount,
    ): void {
        $names = ['foo', 'bar', 'baz', 'another'];
        foreach ($names as $name) {
            $this->getEntityManager()->persist(new Tag($name));
        }
        $this->getEntityManager()->flush();

        $adapter = new TagsPaginatorAdapter($this->repo, TagsParams::fromRawData(['searchTerm' => $searchTerm]), null);

        self::assertCount($expectedSliceSize, $adapter->getSlice($offset, $length));
        self::assertEquals($expectedTotalCount, $adapter->getNbResults());
    }

    public function provideFilters(): iterable
    {
        yield [null, 0, 10, 4, 4];
        yield [null, 2, 10, 2, 4];
        yield [null, 1, 3, 3, 4];
        yield [null, 3, 3, 1, 4];
        yield [null, 0, 2, 2, 4];
        yield ['ba', 0, 10, 2, 2];
        yield ['ba', 0, 1, 1, 2];
        yield ['foo', 0, 10, 1, 1];
        yield ['a', 0, 10, 3, 3];
    }
}
