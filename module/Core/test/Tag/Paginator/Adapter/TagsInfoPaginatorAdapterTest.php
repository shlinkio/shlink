<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Tag\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Repository\TagRepositoryInterface;
use Shlinkio\Shlink\Core\Tag\Model\TagsParams;
use Shlinkio\Shlink\Core\Tag\Paginator\Adapter\TagsInfoPaginatorAdapter;

class TagsInfoPaginatorAdapterTest extends TestCase
{
    use ProphecyTrait;

    private TagsInfoPaginatorAdapter $adapter;
    private ObjectProphecy $repo;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(TagRepositoryInterface::class);
        $this->adapter = new TagsInfoPaginatorAdapter($this->repo->reveal(), TagsParams::fromRawData([]), null);
    }

    /** @test */
    public function getSliceIsDelegatedToRepository(): void
    {
        $findTags = $this->repo->findTagsWithInfo(Argument::cetera())->willReturn([]);

        $this->adapter->getSlice(1, 1);

        $findTags->shouldHaveBeenCalledOnce();
    }

    /** @test */
    public function getNbResultsIsDelegatedToRepository(): void
    {
        $match = $this->repo->matchSingleScalarResult(Argument::cetera())->willReturn(3);

        $result = $this->adapter->getNbResults();

        self::assertEquals(3, $result);
        $match->shouldHaveBeenCalledOnce();
    }
}
