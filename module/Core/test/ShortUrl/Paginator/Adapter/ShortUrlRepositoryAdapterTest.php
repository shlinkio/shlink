<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Paginator\Adapter;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Paginator\Adapter\ShortUrlRepositoryAdapter;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsCountFiltering;
use Shlinkio\Shlink\Core\ShortUrl\Persistence\ShortUrlsListFiltering;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlRepositoryAdapterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $repo;

    public function setUp(): void
    {
        $this->repo = $this->prophesize(ShortUrlRepositoryInterface::class);
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
        $adapter = new ShortUrlRepositoryAdapter($this->repo->reveal(), $params, null);
        $orderBy = $params->orderBy();
        $dateRange = $params->dateRange();

        $this->repo->findList(
            new ShortUrlsListFiltering(10, 5, $orderBy, $searchTerm, $tags, ShortUrlsParams::TAGS_MODE_ANY, $dateRange),
        )->shouldBeCalledOnce();
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
        $adapter = new ShortUrlRepositoryAdapter($this->repo->reveal(), $params, $apiKey);
        $dateRange = $params->dateRange();

        $this->repo->countList(
            new ShortUrlsCountFiltering($searchTerm, $tags, ShortUrlsParams::TAGS_MODE_ANY, $dateRange, $apiKey),
        )->shouldBeCalledOnce();
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
