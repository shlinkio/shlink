<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Common\Util\DateRange;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ListShortUrlsAction;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;
use Zend\Paginator\Adapter\ArrayAdapter;
use Zend\Paginator\Paginator;

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
        DateRange $expectedDateRange
    ): void {
        $listShortUrls = $this->service->listShortUrls(
            $expectedPage,
            $expectedSearchTerm,
            $expectedTags,
            $expectedOrderBy,
            $expectedDateRange,
        )->willReturn(new Paginator(new ArrayAdapter()));

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
        yield [[], 1, null, [], null, new DateRange()];
        yield [['page' => 10], 10, null, [], null, new DateRange()];
        yield [['page' => null], 1, null, [], null, new DateRange()];
        yield [['page' => '8'], 8, null, [], null, new DateRange()];
        yield [['searchTerm' => $searchTerm = 'foo'], 1, $searchTerm, [], null, new DateRange()];
        yield [['tags' => $tags = ['foo','bar']], 1, null, $tags, null, new DateRange()];
        yield [['orderBy' => $orderBy = 'something'], 1, null, [], $orderBy, new DateRange()];
        yield [[
            'page' => '2',
            'orderBy' => $orderBy = 'something',
            'tags' => $tags = ['one', 'two'],
        ], 2, null, $tags, $orderBy, new DateRange()];
        yield [
            ['startDate' => $date = Chronos::now()->toAtomString()],
            1,
            null,
            [],
            null,
            new DateRange(Chronos::parse($date)),
        ];
        yield [
            ['endDate' => $date = Chronos::now()->toAtomString()],
            1,
            null,
            [],
            null,
            new DateRange(null, Chronos::parse($date)),
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
            new DateRange(Chronos::parse($startDate), Chronos::parse($endDate)),
        ];
    }
}
