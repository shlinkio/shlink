<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\ApiTestDataProviders;
use ShlinkioApiTest\Shlink\Rest\Utils\UrlBuilder;

use function sprintf;

class DeleteShortUrlTest extends ApiTestCase
{
    #[Test, DataProviderExternal(ApiTestDataProviders::class, 'invalidUrlsProvider')]
    public function notFoundErrorIsReturnWhenDeletingInvalidUrl(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(
            self::METHOD_DELETE,
            UrlBuilder::buildShortUrlPath($shortCode, $domain),
            apiKey: $apiKey,
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    #[Test, DataProvider('provideApiVersions')]
    public function expectedTypeIsReturnedBasedOnApiVersion(string $version, string $expectedType): void
    {
        $resp = $this->callApiWithKey(
            self::METHOD_DELETE,
            sprintf('/rest/v%s/short-urls/invalid-short-code', $version),
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals($expectedType, $payload['type']);
    }

    public static function provideApiVersions(): iterable
    {
        yield ['1', 'INVALID_SHORTCODE'];
        yield ['2', 'INVALID_SHORTCODE'];
        yield ['3', 'https://shlink.io/api/error/short-url-not-found'];
    }

    #[Test]
    public function properShortUrlIsDeletedWhenDomainIsProvided(): void
    {
        $fetchWithDomainBefore = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789?domain=example.com');
        $fetchWithoutDomainBefore = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789');
        $deleteResp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/ghi789?domain=example.com');
        $fetchWithDomainAfter = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789?domain=example.com');
        $fetchWithoutDomainAfter = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789');

        self::assertEquals(self::STATUS_OK, $fetchWithDomainBefore->getStatusCode());
        self::assertEquals(self::STATUS_OK, $fetchWithoutDomainBefore->getStatusCode());
        self::assertEquals(self::STATUS_NO_CONTENT, $deleteResp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $fetchWithDomainAfter->getStatusCode());
        self::assertEquals(self::STATUS_OK, $fetchWithoutDomainAfter->getStatusCode());
    }
}
