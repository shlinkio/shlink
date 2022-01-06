<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsPaginatorAdapter;

class TagsPaginatorAdapterTest extends TestCase
{
    use ProphecyTrait;

    private TagsPaginatorAdapter $adapter;
    private ObjectProphecy $repo;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(TagRepositoryInterface::class);
        $this->adapter = new TagsPaginatorAdapter($this->repo->reveal(), TagsParams::fromRawData([]), null);
    }

    /** @test */
    public function getSliceDelegatesToRepository(): void
    {
        $match = $this->repo->match(Argument::cetera())->willReturn([]);

        $this->adapter->getSlice(1, 1);

        $match->shouldHaveBeenCalledOnce();
    }
}
