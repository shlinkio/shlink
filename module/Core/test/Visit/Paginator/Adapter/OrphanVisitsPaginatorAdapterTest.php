<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Paginator\Adapter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\OrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;

class OrphanVisitsPaginatorAdapterTest extends TestCase
{
    private OrphanVisitsPaginatorAdapter $adapter;
    private MockObject & VisitRepositoryInterface $repo;
    private VisitsParams $params;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(VisitRepositoryInterface::class);
        $this->params = VisitsParams::fromRawData([]);
        $this->adapter = new OrphanVisitsPaginatorAdapter($this->repo, $this->params);
    }

    /** @test */
    public function countDelegatesToRepository(): void
    {
        $expectedCount = 5;
        $this->repo->expects($this->once())->method('countOrphanVisits')->with(
            new VisitsCountFiltering($this->params->dateRange),
        )->willReturn($expectedCount);

        $result = $this->adapter->getNbResults();

        self::assertEquals($expectedCount, $result);
    }

    /**
     * @test
     * @dataProvider provideLimitAndOffset
     */
    public function getSliceDelegatesToRepository(int $limit, int $offset): void
    {
        $visitor = Visitor::emptyInstance();
        $list = [Visit::forRegularNotFound($visitor), Visit::forInvalidShortUrl($visitor)];
        $this->repo->expects($this->once())->method('findOrphanVisits')->with(
            new VisitsListFiltering($this->params->dateRange, $this->params->excludeBots, null, $limit, $offset),
        )->willReturn($list);

        $result = $this->adapter->getSlice($offset, $limit);

        self::assertEquals($list, $result);
    }

    public function provideLimitAndOffset(): iterable
    {
        yield [1, 5];
        yield [10, 4];
        yield [30, 18];
    }
}
