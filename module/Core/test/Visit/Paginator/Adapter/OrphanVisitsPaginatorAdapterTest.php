<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Paginator\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Model\VisitsParams;
use Shlinkio\Shlink\Core\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\OrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;

class OrphanVisitsPaginatorAdapterTest extends TestCase
{
    use ProphecyTrait;

    private OrphanVisitsPaginatorAdapter $adapter;
    private ObjectProphecy $repo;
    private VisitsParams $params;

    protected function setUp(): void
    {
        $this->repo = $this->prophesize(VisitRepositoryInterface::class);
        $this->params = VisitsParams::fromRawData([]);
        $this->adapter = new OrphanVisitsPaginatorAdapter($this->repo->reveal(), $this->params);
    }

    /** @test */
    public function countDelegatesToRepository(): void
    {
        $expectedCount = 5;
        $repoCount = $this->repo->countOrphanVisits(
            new VisitsCountFiltering($this->params->getDateRange()),
        )->willReturn($expectedCount);

        $result = $this->adapter->getNbResults();

        self::assertEquals($expectedCount, $result);
        $repoCount->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     * @dataProvider provideLimitAndOffset
     */
    public function getSliceDelegatesToRepository(int $limit, int $offset): void
    {
        $visitor = Visitor::emptyInstance();
        $list = [Visit::forRegularNotFound($visitor), Visit::forInvalidShortUrl($visitor)];
        $repoFind = $this->repo->findOrphanVisits(
            new VisitsListFiltering($this->params->getDateRange(), $this->params->excludeBots(), null, $limit, $offset),
        )->willReturn($list);

        $result = $this->adapter->getSlice($offset, $limit);

        self::assertEquals($list, $result);
        $repoFind->shouldHaveBeenCalledOnce();
    }

    public function provideLimitAndOffset(): iterable
    {
        yield [1, 5];
        yield [10, 4];
        yield [30, 18];
    }
}
