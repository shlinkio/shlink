<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag\Paginator\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;
use Shlinkio\Shlink\Core\Tag\Repository\TagRepositoryInterface;

class TagsPaginatorAdapterTest extends TestCase
{
    private TagsPaginatorAdapter $adapter;
    private MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(TagRepositoryInterface::class);
        $this->adapter = new TagsPaginatorAdapter($this->repo, TagsParams::fromRawData([]), null);
    }

    /** @test */
    public function getSliceDelegatesToRepository(): void
    {
        $this->repo->expects($this->once())->method('match')->willReturn([]);
        $this->adapter->getSlice(1, 1);
    }
}
