<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

class DeleteShortUrlTest extends ApiTestCase
{
    use NotFoundUrlHelpersTrait;

    /**
     * @test
     * @dataProvider provideInvalidUrls
     */
    public function notFoundErrorIsReturnWhenDeletingInvalidUrl(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, $this->buildShortUrlPath($shortCode, $domain), [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    /** @test */
    public function unprocessableEntityIsReturnedWhenTryingToDeleteUrlWithTooManyVisits(): void
    {
        // Generate visits first
        for ($i = 0; $i < 20; $i++) {
            self::assertEquals(self::STATUS_FOUND, $this->callShortUrl('abc123')->getStatusCode());
        }
        $expectedDetail = 'Impossible to delete short URL with short code "abc123", since it has more than "15" '
            . 'visits.';

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/abc123');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNPROCESSABLE_ENTITY, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNPROCESSABLE_ENTITY, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE_DELETION', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Cannot delete short URL', $payload['title']);
    }

    /** @test */
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
