<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag\Paginator\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsInfoPaginatorAdapter;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepositoryInterface;

class TagsInfoPaginatorAdapterTest extends TestCase
{
    private TagsInfoPaginatorAdapter $adapter;
    private MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(TagRepositoryInterface::class);
        $this->adapter = new TagsInfoPaginatorAdapter($this->repo, TagsParams::fromRawData([]), null);
    }

    /** @test */
    public function getSliceIsDelegatedToRepository(): void
    {
        $this->repo->expects($this->once())->method('findTagsWithInfo')->willReturn([]);
        $this->adapter->getSlice(1, 1);
    }

    /** @test */
    public function getNbResultsIsDelegatedToRepository(): void
    {
        $this->repo->expects($this->once())->method('matchSingleScalarResult')->willReturn(3);

        $result = $this->adapter->getNbResults();

        self::assertEquals(3, $result);
    }
}
