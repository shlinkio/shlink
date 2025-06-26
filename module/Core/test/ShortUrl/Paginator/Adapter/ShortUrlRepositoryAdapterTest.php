<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Paginator\Adapter;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlListRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapterTest extends TestCase
{
    private MockObject & ShortUrlListRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlListRepositoryInterface::class);
    }

    #[Test, DataProvider('provideFilteringArgs')]
    public function getItemsFallsBackToFindList(
        string|null $searchTerm = null,
        array $tags = [],
        string|null $startDate = null,
        string|null $endDate = null,
        string|null $orderBy = null,
    ): void {
        $params = ShortUrlsParams::fromRawData([
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'orderBy' => $orderBy,
        ]);
        $adapter = new ShortUrlRepositoryAdapter($this->repo, $params, null, '');
        $orderBy = $params->orderBy;
        $dateRange = $params->dateRange;

        $this->repo->expects($this->once())->method('findList')->with(
            new ShortUrlsListFiltering(10, 5, $orderBy, $searchTerm, $tags, TagsMode::ANY, $dateRange),
        );

        $adapter->getSlice(5, 10);
    }

    #[Test, DataProvider('provideFilteringArgs')]
    public function countFallsBackToCountList(
        string|null $searchTerm = null,
        array $tags = [],
        string|null $startDate = null,
        string|null $endDate = null,
    ): void {
        $params = ShortUrlsParams::fromRawData([
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
        $apiKey = ApiKey::create();
        $adapter = new ShortUrlRepositoryAdapter($this->repo, $params, $apiKey, '');
        $dateRange = $params->dateRange;

        $this->repo->expects($this->once())->method('countList')->with(
            new ShortUrlsCountFiltering($searchTerm, $tags, TagsMode::ANY, $dateRange, apiKey: $apiKey),
        );
        $adapter->getNbResults();
    }

    public static function provideFilteringArgs(): iterable
    {
        yield [];
        yield ['search'];
        yield ['search', []];
        yield ['search', ['foo', 'bar']];
        yield ['search', ['foo', 'bar'], null, null];
        yield ['search', ['foo', 'bar'], Chronos::now()->toAtomString(), null];
        yield ['search', ['foo', 'bar'], null, Chronos::now()->toAtomString()];
        yield ['search', ['foo', 'bar'], Chronos::now()->toAtomString(), Chronos::now()->toAtomString()];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString(), null];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString()];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString(), Chronos::now()->toAtomString()];
    }
}
