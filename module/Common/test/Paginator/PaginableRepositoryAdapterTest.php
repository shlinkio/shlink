<?php
namespace ShlinkioTest\Shlink\Common\Paginator;

use PHPUnit_Framework_TestCase as TestCase;
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
        $this->adapter = new PaginableRepositoryAdapter($this->repo->reveal(), 'search', 'order');
    }

    /**
     * @test
     */
    public function getItemsFallbacksToFindList()
    {
        $this->repo->findList(10, 5, 'search', 'order')->shouldBeCalledTimes(1);
        $this->adapter->getItems(5, 10);
    }

    /**
     * @test
     */
    public function countFallbacksToCountList()
    {
        $this->repo->countList('search')->shouldBeCalledTimes(1);
        $this->adapter->count();
    }
}
