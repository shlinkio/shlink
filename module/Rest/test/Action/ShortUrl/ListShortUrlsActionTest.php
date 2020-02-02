<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Paginator\Adapter\ArrayAdapter;
use Laminas\Paginator\Paginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ListShortUrlsAction;

class ListShortUrlsActionTest extends TestCase
{
    private ListShortUrlsAction $action;
    private ObjectProphecy $service;
    private ObjectProphecy $logger;

    public function setUp(): void
    {
        $this->service = $this->prophesize(ShortUrlService::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->action = new ListShortUrlsAction($this->service->reveal(), [
            'hostname' => 'doma.in',
            'schema' => 'https',
        ], $this->logger->reveal());
    }

    /**
     * @test
     * @dataProvider provideFilteringData
     */
    public function properListReturnsSuccessResponse(
        array $query,
        int $expectedPage,
        ?string $expectedSearchTerm,
        array $expectedTags,
        ?string $expectedOrderBy,
        ?string $startDate = null,
        ?string $endDate = null
    ): void {
        $listShortUrls = $this->service->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => $expectedPage,
            'searchTerm' => $expectedSearchTerm,
            'tags' => $expectedTags,
            'orderBy' => $expectedOrderBy,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]))->willReturn(new Paginator(new ArrayAdapter()));

        /** @var JsonResponse $response */
        $response = $this->action->handle((new ServerRequest())->withQueryParams($query));
        $payload = $response->getPayload();

        $this->assertArrayHasKey('shortUrls', $payload);
        $this->assertArrayHasKey('data', $payload['shortUrls']);
        $this->assertEquals([], $payload['shortUrls']['data']);
        $this->assertEquals(200, $response->getStatusCode());
        $listShortUrls->shouldHaveBeenCalledOnce();
    }

    public function provideFilteringData(): iterable
    {
        yield [[], 1, null, [], null];
        yield [['page' => 10], 10, null, [], null];
        yield [['page' => null], 1, null, [], null];
        yield [['page' => '8'], 8, null, [], null];
        yield [['searchTerm' => $searchTerm = 'foo'], 1, $searchTerm, [], null];
        yield [['tags' => $tags = ['foo','bar']], 1, null, $tags, null];
        yield [['orderBy' => $orderBy = 'something'], 1, null, [], $orderBy];
        yield [[
            'page' => '2',
            'orderBy' => $orderBy = 'something',
            'tags' => $tags = ['one', 'two'],
        ], 2, null, $tags, $orderBy];
        yield [
            ['startDate' => $date = Chronos::now()->toAtomString()],
            1,
            null,
            [],
            null,
            $date,
        ];
        yield [
            ['endDate' => $date = Chronos::now()->toAtomString()],
            1,
            null,
            [],
            null,
            null,
            $date,
        ];
        yield [
            [
                'startDate' => $startDate = Chronos::now()->subDays(10)->toAtomString(),
                'endDate' => $endDate = Chronos::now()->toAtomString(),
            ],
            1,
            null,
            [],
            null,
            $startDate,
            $endDate,
        ];
    }
}
