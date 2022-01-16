<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\TagVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsForTagPaginatorAdapterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $repo;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(VisitRepositoryInterface::class);
    }

    /** @test */
    public function repoIsCalledEveryTimeItemsAreFetched(): void
    {
        $count = 3;
        $limit = 1;
        $offset = 5;
        $adapter = $this->createAdapter(null);
        $findVisits = $this->repo->findVisitsByTag(
            'foo',
            new VisitsListFiltering(DateRange::emptyInstance(), false, null, $limit, $offset),
        )->willReturn([]);

        for ($i = 0; $i < $count; $i++) {
            $adapter->getSlice($offset, $limit);
        }

        $findVisits->shouldHaveBeenCalledTimes($count);
    }

    /** @test */
    public function repoIsCalledOnlyOnceForCount(): void
    {
        $count = 3;
        $apiKey = ApiKey::create();
        $adapter = $this->createAdapter($apiKey);
        $countVisits = $this->repo->countVisitsByTag(
            'foo',
            new VisitsCountFiltering(DateRange::emptyInstance(), false, $apiKey),
        )->willReturn(3);

        for ($i = 0; $i < $count; $i++) {
            $adapter->getNbResults();
        }

        $countVisits->shouldHaveBeenCalledOnce();
    }

    private function createAdapter(?ApiKey $apiKey): TagVisitsPaginatorAdapter
    {
        return new TagVisitsPaginatorAdapter(
            $this->repo->reveal(),
            'foo',
            VisitsParams::fromRawData([]),
            $apiKey,
        );
    }
}
