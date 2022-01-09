<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ListShortUrlsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListShortUrlsActionTest extends TestCase
{
    use ProphecyTrait;

    private ListShortUrlsAction $action;
    private ObjectProphecy $service;

    public function setUp(): void
    {
        $this->service = $this->prophesize(ShortUrlService::class);

        $this->action = new ListShortUrlsAction($this->service->reveal(), new ShortUrlDataTransformer(
            new ShortUrlStringifier([
                'hostname' => 'doma.in',
                'schema' => 'https',
            ]),
        ));
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
        ?string $endDate = null,
    ): void {
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withQueryParams($query)
                                                      ->withAttribute(ApiKey::class, $apiKey);
        $listShortUrls = $this->service->listShortUrls(ShortUrlsParams::fromRawData([
            'page' => $expectedPage,
            'searchTerm' => $expectedSearchTerm,
            'tags' => $expectedTags,
            'orderBy' => $expectedOrderBy,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]), $apiKey)->willReturn(new Paginator(new ArrayAdapter([])));

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertArrayHasKey('shortUrls', $payload);
        self::assertArrayHasKey('data', $payload['shortUrls']);
        self::assertEquals([], $payload['shortUrls']['data']);
        self::assertEquals(200, $response->getStatusCode());
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
        yield [['orderBy' => $orderBy = 'longUrl'], 1, null, [], $orderBy];
        yield [[
            'page' => '2',
            'orderBy' => $orderBy = 'visits',
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
