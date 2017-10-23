<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\Paginator;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Common\Repository\PaginableRepositoryInterface;

class PaginableRepositoryAdapterTest extends TestCase
{
    /**
     * @var PaginableRepositoryAdapter
     */
    protected $adapter;
    /**
     * @var ObjectProphecy
     */
    protected $repo;

    public function setUp()
    {
        $this->repo = $this->prophesize(PaginableRepositoryInterface::class);
        $this->adapter = new PaginableRepositoryAdapter($this->repo->reveal(), 'search', ['foo', 'bar'], 'order');
    }

    /**
     * @test
     */
    public function getItemsFallbacksToFindList()
    {
        $this->repo->findList(10, 5, 'search', ['foo', 'bar'], 'order')->shouldBeCalledTimes(1);
        $this->adapter->getItems(5, 10);
    }

    /**
     * @test
     */
    public function countFallbacksToCountList()
    {
        $this->repo->countList('search', ['foo', 'bar'])->shouldBeCalledTimes(1);
        $this->adapter->count();
    }
}
