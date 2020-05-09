<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Paginator\Adapter\VisitsForTagPaginatorAdapter;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;

class VisitsForTagPaginatorAdapterTest extends TestCase
{
    private VisitsForTagPaginatorAdapter $adapter;
    private ObjectProphecy $repo;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(VisitRepositoryInterface::class);
        $this->adapter = new VisitsForTagPaginatorAdapter($this->repo->reveal(), 'foo', VisitsParams::fromRawData([]));
    }

    /** @test */
    public function repoIsCalledEveryTimeItemsAreFetched(): void
    {
        $count = 3;
        $limit = 1;
        $offset = 5;
        $findVisits = $this->repo->findVisitsByTag('foo', new DateRange(), $limit, $offset)->willReturn([]);

        for ($i = 0; $i < $count; $i++) {
            $this->adapter->getItems($offset, $limit);
        }

        $findVisits->shouldHaveBeenCalledTimes($count);
    }

    /** @test */
    public function repoIsCalledOnlyOnceForCount(): void
    {
        $count = 3;
        $countVisits = $this->repo->countVisitsByTag('foo', new DateRange())->willReturn(3);

        for ($i = 0; $i < $count; $i++) {
            $this->adapter->count();
        }

        $countVisits->shouldHaveBeenCalledOnce();
    }
}
