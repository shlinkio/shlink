<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Paginator\Adapter;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;

class ShortUrlRepositoryAdapterTest extends TestCase
{
    /** @var ObjectProphecy */
    private $repo;

    public function setUp(): void
    {
        $this->repo = $this->prophesize(ShortUrlRepositoryInterface::class);
    }

    /**
     * @test
     * @dataProvider provideFilteringArgs
     */
    public function getItemsFallsBackToFindList(
        $searchTerm = null,
        array $tags = [],
        ?DateRange $dateRange = null,
        $orderBy = null
    ): void {
        $adapter = new ShortUrlRepositoryAdapter($this->repo->reveal(), $searchTerm, $tags, $orderBy, $dateRange);

        $this->repo->findList(10, 5, $searchTerm, $tags, $orderBy, $dateRange)->shouldBeCalledOnce();
        $adapter->getItems(5, 10);
    }

    /**
     * @test
     * @dataProvider provideFilteringArgs
     */
    public function countFallsBackToCountList($searchTerm = null, array $tags = [], ?DateRange $dateRange = null): void
    {
        $adapter = new ShortUrlRepositoryAdapter($this->repo->reveal(), $searchTerm, $tags, null, $dateRange);

        $this->repo->countList($searchTerm, $tags, $dateRange)->shouldBeCalledOnce();
        $adapter->count();
    }

    public function provideFilteringArgs(): iterable
    {
        yield [];
        yield ['search'];
        yield ['search', []];
        yield ['search', ['foo', 'bar']];
        yield ['search', ['foo', 'bar'], null, 'order'];
        yield ['search', ['foo', 'bar'], new DateRange(), 'order'];
        yield ['search', ['foo', 'bar'], new DateRange(Chronos::now()), 'order'];
        yield ['search', ['foo', 'bar'], new DateRange(null, Chronos::now()), 'order'];
        yield ['search', ['foo', 'bar'], new DateRange(Chronos::now(), Chronos::now()), 'order'];
        yield ['search', ['foo', 'bar'], new DateRange(Chronos::now())];
        yield [null, ['foo', 'bar'], new DateRange(Chronos::now(), Chronos::now())];
    }
}
