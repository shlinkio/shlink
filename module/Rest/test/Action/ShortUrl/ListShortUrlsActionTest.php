<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Action\ShortUrl;

use Cake\Chronos\Chronos;
use CuyZ\Valinor\MapperBuilder;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Pagerfanta\Adapter\ArrayAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Common\Paginator\Paginator;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlStringifier;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlsParams;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlListServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformer;
use Shlinkio\Shlink\Rest\Action\ShortUrl\ListShortUrlsAction;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ListShortUrlsActionTest extends TestCase
{
    private ListShortUrlsAction $action;
    private MockObject & ShortUrlListServiceInterface $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(ShortUrlListServiceInterface::class);

        $this->action = new ListShortUrlsAction($this->service, new ShortUrlDataTransformer(
            new ShortUrlStringifier(new UrlShortenerOptions('s.test')),
        ), new MapperBuilder()->mapper());
    }

    #[Test, DataProvider('provideFilteringData')]
    public function properListReturnsSuccessResponse(array $query): void
    {
        $apiKey = ApiKey::create();
        $request = ServerRequestFactory::fromGlobals()->withQueryParams($query)
                                                      ->withAttribute(ApiKey::class, $apiKey);
        $this->service->expects($this->once())->method('listShortUrls')->with(
            $this->isInstanceOf(ShortUrlsParams::class),
            $apiKey,
        )->willReturn(new Paginator(new ArrayAdapter([])));

        /** @var JsonResponse $response */
        $response = $this->action->handle($request);
        $payload = $response->getPayload();

        self::assertArrayHasKey('shortUrls', $payload);
        self::assertArrayHasKey('data', $payload['shortUrls']);
        self::assertEquals([], $payload['shortUrls']['data']);
        self::assertEquals(200, $response->getStatusCode());
    }

    public static function provideFilteringData(): iterable
    {
        yield [[]];
        yield [['page' => 10]];
        yield [['searchTerm' => 'foo']];
        yield [['tags' => ['foo','bar']]];
        yield [['orderBy' => 'longUrl']];
        yield [[
            'page' => 2,
            'orderBy' => 'visits',
            'tags' => ['one', 'two'],
        ]];
        yield [['startDate' => Chronos::now()->toAtomString()]];
        yield [['endDate' => Chronos::now()->toAtomString()]];
        yield [[
            'startDate' => Chronos::now()->subDays(10)->toAtomString(),
            'endDate' => Chronos::now()->toAtomString(),
        ]];
    }
}
