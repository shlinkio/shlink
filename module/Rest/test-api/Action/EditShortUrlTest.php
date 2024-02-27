<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\ApiTestDataProviders;
use ShlinkioApiTest\Shlink\Rest\Utils\UrlBuilder;

use function sprintf;

class EditShortUrlTest extends ApiTestCase
{
    #[Test, DataProvider('provideMeta')]
    public function metadataCanBeReset(array $meta): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);
        $resetMeta = [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ];

        $editWithProvidedMeta = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => $meta]);
        $metaAfterEditing = $this->findShortUrlMetaByShortCode($shortCode);

        $editWithResetMeta = $this->callApiWithKey(self::METHOD_PATCH, $url, [
            RequestOptions::JSON => $resetMeta,
        ]);
        $metaAfterResetting = $this->findShortUrlMetaByShortCode($shortCode);

        self::assertEquals(self::STATUS_OK, $editWithProvidedMeta->getStatusCode());
        self::assertEquals(self::STATUS_OK, $editWithResetMeta->getStatusCode());
        self::assertEquals($resetMeta, $metaAfterResetting);
        self::assertArraySubset($meta, $metaAfterEditing);
    }

    private static function assertArraySubset(array $a, array $b): void
    {
        foreach ($a as $key => $expectedValue) {
            self::assertEquals($expectedValue, $b[$key]);
        }
    }

    public static function provideMeta(): iterable
    {
        $now = Chronos::now();

        yield [['validSince' => $now->addMonths(1)->toAtomString()]];
        yield [['validUntil' => $now->subMonths(1)->toAtomString()]];
        yield [['maxVisits' => 20]];
        yield [['validUntil' => $now->addYears(1)->toAtomString(), 'maxVisits' => 100]];
        yield [[
            'validSince' => $now->subYears(1)->toAtomString(),
            'validUntil' => $now->addYears(1)->toAtomString(),
            'maxVisits' => 100,
        ]];
    }

    private function findShortUrlMetaByShortCode(string $shortCode): array
    {
        $matchingShortUrl = $this->getJsonResponsePayload(
            $this->callApiWithKey(self::METHOD_GET, '/short-urls/' . $shortCode),
        );

        return $matchingShortUrl['meta'] ?? [];
    }

    public function longUrlCanBeEdited(): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);

        $resp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => [
            'longUrl' => 'https://shlink.io',
        ]]);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
    }

    #[Test, DataProviderExternal(ApiTestDataProviders::class, 'invalidUrlsProvider')]
    public function tryingToEditInvalidUrlReturnsNotFoundError(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $url = UrlBuilder::buildShortUrlPath($shortCode, $domain);
        $resp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => []], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    #[Test]
    public function providingInvalidDataReturnsBadRequest(): void
    {
        $expectedDetail = 'Provided data is not valid';

        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => [
            'maxVisits' => 'not_a_number',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/invalid-data', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
    }

    #[Test, DataProvider('provideDomains')]
    public function metadataIsEditedOnProperShortUrlBasedOnDomain(?string $domain, string $expectedUrl): void
    {
        $shortCode = 'ghi789';
        $url = new Uri(sprintf('/short-urls/%s', $shortCode));

        if ($domain !== null) {
            $url = $url->withQuery(Query::build(['domain' => $domain]));
        }

        $editResp = $this->callApiWithKey(self::METHOD_PATCH, (string) $url, [RequestOptions::JSON => [
            'maxVisits' => 100,
        ]]);
        $editedShortUrl = $this->getJsonResponsePayload($editResp);

        self::assertEquals(self::STATUS_OK, $editResp->getStatusCode());
        self::assertEquals($domain, $editedShortUrl['domain']);
        self::assertEquals($expectedUrl, $editedShortUrl['longUrl']);
        self::assertEquals(100, $editedShortUrl['meta']['maxVisits'] ?? null);
    }

    public static function provideDomains(): iterable
    {
        yield 'domain' => [
            'example.com',
            'https://blog.alejandrocelaya.com/2019/04/27/considerations-to-properly-use-open-source-software-projects/',
        ];
        yield 'no domain' => [null, 'https://shlink.io/documentation/'];
    }
}
