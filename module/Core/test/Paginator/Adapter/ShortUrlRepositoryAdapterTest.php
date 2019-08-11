<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class ShortUrlRepositoryAdapterTest extends TestCase
{
    /** @var ShortUrlRepositoryAdapter */
    private $adapter;
    /** @var ObjectProphecy */
    private $repo;

    public function setUp(): void
    {
        $this->repo = $this->prophesize(ShortUrlRepositoryInterface::class);
        $this->adapter = new ShortUrlRepositoryAdapter($this->repo->reveal(), 'search', ['foo', 'bar'], 'order');
    }

    /** @test */
    public function getItemsFallbacksToFindList(): void
    {
        $this->repo->findList(10, 5, 'search', ['foo', 'bar'], 'order')->shouldBeCalledOnce();
        $this->adapter->getItems(5, 10);
    }

    /** @test */
    public function countFallbacksToCountList(): void
    {
        $this->repo->countList('search', ['foo', 'bar'])->shouldBeCalledOnce();
        $this->adapter->count();
    }
}
