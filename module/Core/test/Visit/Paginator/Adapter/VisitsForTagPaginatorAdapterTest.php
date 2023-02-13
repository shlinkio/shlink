<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Paginator\Adapter;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\TagVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class VisitsForTagPaginatorAdapterTest extends TestCase
{
    private MockObject & VisitRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(VisitRepositoryInterface::class);
    }

    #[Test]
    public function repoIsCalledEveryTimeItemsAreFetched(): void
    {
        $count = 3;
        $limit = 1;
        $offset = 5;
        $adapter = $this->createAdapter(null);
        $this->repo->expects($this->exactly($count))->method('findVisitsByTag')->with(
            'foo',
            new VisitsListFiltering(DateRange::allTime(), false, null, $limit, $offset),
        )->willReturn([]);

        for ($i = 0; $i < $count; $i++) {
            $adapter->getSlice($offset, $limit);
        }
    }

    #[Test]
    public function repoIsCalledOnlyOnceForCount(): void
    {
        $count = 3;
        $apiKey = ApiKey::create();
        $adapter = $this->createAdapter($apiKey);
        $this->repo->expects($this->once())->method('countVisitsByTag')->with(
            'foo',
            new VisitsCountFiltering(DateRange::allTime(), false, $apiKey),
        )->willReturn(3);

        for ($i = 0; $i < $count; $i++) {
            $adapter->getNbResults();
        }
    }

    private function createAdapter(?ApiKey $apiKey): TagVisitsPaginatorAdapter
    {
        return new TagVisitsPaginatorAdapter($this->repo, 'foo', VisitsParams::fromRawData([]), $apiKey);
    }
}
