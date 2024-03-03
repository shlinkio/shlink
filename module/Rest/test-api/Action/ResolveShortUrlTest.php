<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\ApiTestDataProviders;
use ShlinkioApiTest\Shlink\Rest\Utils\UrlBuilder;

use function sprintf;

class ResolveShortUrlTest extends ApiTestCase
{
    #[Test, DataProvider('provideDisabledMeta')]
    public function shortUrlIsProperlyResolvedEvenWhenNotEnabled(array $disabledMeta): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);
        $this->callShortUrl($shortCode);

        $editResp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => $disabledMeta]);
        $visitResp = $this->callShortUrl($shortCode);
        $fetchResp = $this->callApiWithKey(self::METHOD_GET, $url);

        self::assertEquals(self::STATUS_OK, $editResp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $visitResp->getStatusCode());
        self::assertEquals(self::STATUS_OK, $fetchResp->getStatusCode());
    }

    public static function provideDisabledMeta(): iterable
    {
        $now = Chronos::now();

        yield 'future validSince' => [['validSince' => $now->addMonths(1)->toAtomString()]];
        yield 'past validUntil' => [['validUntil' => $now->subMonths(1)->toAtomString()]];
        yield 'maxVisits reached' => [['maxVisits' => 1]];
    }

    #[Test, DataProviderExternal(ApiTestDataProviders::class, 'invalidUrlsProvider')]
    public function tryingToResolveInvalidUrlReturnsNotFoundError(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(
            self::METHOD_GET,
            UrlBuilder::buildShortUrlPath($shortCode, $domain),
            apiKey: $apiKey,
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }
}
