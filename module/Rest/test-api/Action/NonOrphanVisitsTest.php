<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class NonOrphanVisitsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideQueries
     */
    public function properVisitsAreReturnedBasedInQuery(array $query, int $totalItems, int $returnedItems): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/non-orphan', [RequestOptions::QUERY => $query]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals($totalItems, $payload['visits']['pagination']['totalItems'] ?? Paginator::ALL_ITEMS);
        self::assertCount($returnedItems, $payload['visits']['data'] ?? []);
    }

    public function provideQueries(): iterable
    {
        yield 'all data' => [[], 7, 7];
        yield 'middle page' => [['page' => 2, 'itemsPerPage' => 3], 7, 3];
        yield 'last page' => [['page' => 3, 'itemsPerPage' => 3], 7, 1];
        yield 'bots excluded' => [['excludeBots' => 'true'], 6, 6];
        yield 'bots excluded and pagination' => [['excludeBots' => 'true', 'page' => 1, 'itemsPerPage' => 4], 6, 4];
        yield 'date filter' => [['startDate' => Chronos::now()->addDay()->toAtomString()], 0, 0];
    }
}
