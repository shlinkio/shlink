<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Paginator\Adapter;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\Model\TagsMode;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapterTest extends TestCase
{
    private MockObject & ShortUrlRepositoryInterface $repo;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(ShortUrlRepositoryInterface::class);
    }

    /**
     * @test
     * @dataProvider provideFilteringArgs
     */
    public function getItemsFallsBackToFindList(
        ?string $searchTerm = null,
        array $tags = [],
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $orderBy = null,
    ): void {
        $params = ShortUrlsParams::fromRawData([
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'orderBy' => $orderBy,
        ]);
        $adapter = new ShortUrlRepositoryAdapter($this->repo, $params, null);
        $orderBy = $params->orderBy();
        $dateRange = $params->dateRange();

        $this->repo->expects($this->once())->method('findList')->with(
            new ShortUrlsListFiltering(10, 5, $orderBy, $searchTerm, $tags, TagsMode::ANY, $dateRange),
        );

        $adapter->getSlice(5, 10);
    }

    /**
     * @test
     * @dataProvider provideFilteringArgs
     */
    public function countFallsBackToCountList(
        ?string $searchTerm = null,
        array $tags = [],
        ?string $startDate = null,
        ?string $endDate = null,
    ): void {
        $params = ShortUrlsParams::fromRawData([
            'searchTerm' => $searchTerm,
            'tags' => $tags,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
        $apiKey = ApiKey::create();
        $adapter = new ShortUrlRepositoryAdapter($this->repo, $params, $apiKey);
        $dateRange = $params->dateRange();

        $this->repo->expects($this->once())->method('countList')->with(
            new ShortUrlsCountFiltering($searchTerm, $tags, TagsMode::ANY, $dateRange, $apiKey),
        );
        $adapter->getNbResults();
    }

    public function provideFilteringArgs(): iterable
    {
        yield [];
        yield ['search'];
        yield ['search', []];
        yield ['search', ['foo', 'bar']];
        yield ['search', ['foo', 'bar'], null, null, 'longUrl'];
        yield ['search', ['foo', 'bar'], Chronos::now()->toAtomString(), null, 'longUrl'];
        yield ['search', ['foo', 'bar'], null, Chronos::now()->toAtomString(), 'longUrl'];
        yield ['search', ['foo', 'bar'], Chronos::now()->toAtomString(), Chronos::now()->toAtomString(), 'longUrl'];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString(), null, 'longUrl'];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString()];
        yield [null, ['foo', 'bar'], Chronos::now()->toAtomString(), Chronos::now()->toAtomString()];
    }
}
