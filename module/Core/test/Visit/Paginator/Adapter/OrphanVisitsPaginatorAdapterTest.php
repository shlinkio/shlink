<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Visit\Paginator\Adapter;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\OrphanVisitsParams;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\Paginator\Adapter\OrphanVisitsPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\OrphanVisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class OrphanVisitsPaginatorAdapterTest extends TestCase
{
    private OrphanVisitsPaginatorAdapter $adapter;
    private MockObject & VisitRepositoryInterface $repo;
    private OrphanVisitsParams $params;
    private ApiKey $apiKey;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(VisitRepositoryInterface::class);
        $this->params = new OrphanVisitsParams();
        $this->apiKey = ApiKey::create();

        $this->adapter = new OrphanVisitsPaginatorAdapter(
            $this->repo,
            $this->params,
            $this->apiKey,
            new UrlShortenerOptions(),
        );
    }

    #[Test]
    public function countDelegatesToRepository(): void
    {
        $expectedCount = 5;
        $this->repo->expects($this->once())->method('countOrphanVisits')->with(
            new OrphanVisitsCountFiltering($this->params->dateRange, apiKey: $this->apiKey),
        )->willReturn($expectedCount);

        $result = $this->adapter->getNbResults();

        self::assertEquals($expectedCount, $result);
    }

    /**
     * @param int<0, max> $limit
     * @param int<0, max> $offset
     */
    #[Test, DataProvider('provideLimitAndOffset')]
    public function getSliceDelegatesToRepository(int $limit, int $offset): void
    {
        $visitor = Visitor::empty();
        $list = [Visit::forRegularNotFound($visitor), Visit::forInvalidShortUrl($visitor)];
        $this->repo->expects($this->once())->method('findOrphanVisits')->with(new OrphanVisitsListFiltering(
            dateRange: $this->params->dateRange,
            excludeBots: $this->params->excludeBots,
            apiKey: $this->apiKey,
            limit: $limit,
            offset: $offset,
        ))->willReturn($list);

        $result = $this->adapter->getSlice($offset, $limit);

        self::assertEquals($list, $result);
    }

    public static function provideLimitAndOffset(): iterable
    {
        yield [1, 5];
        yield [10, 4];
        yield [30, 18];
    }
}
