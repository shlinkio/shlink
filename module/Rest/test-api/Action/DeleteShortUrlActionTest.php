<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

class DeleteShortUrlActionTest extends ApiTestCase
{
    use NotFoundUrlHelpersTrait;

    /**
     * @test
     * @dataProvider provideInvalidUrls
     */
    public function notFoundErrorIsReturnWhenDeletingInvalidUrl(
        string $shortCode,
        ?string $domain,
        string $expectedDetail
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, $this->buildShortUrlPath($shortCode, $domain));
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Short URL not found', $payload['title']);
        $this->assertEquals($shortCode, $payload['shortCode']);
        $this->assertEquals($domain, $payload['domain'] ?? null);
    }

    /** @test */
    public function unprocessableEntityIsReturnedWhenTryingToDeleteUrlWithTooManyVisits(): void
    {
        // Generate visits first
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(self::STATUS_FOUND, $this->callShortUrl('abc123')->getStatusCode());
        }
        $expectedDetail = 'Impossible to delete short URL with short code "abc123" since it has more than "15" visits.';

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/abc123');
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNPROCESSABLE_ENTITY, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_UNPROCESSABLE_ENTITY, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE_DELETION', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Cannot delete short URL', $payload['title']);
    }

    /** @test */
    public function properShortUrlIsDeletedWhenDomainIsProvided(): void
    {
        $fetchWithDomainBefore = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789?domain=example.com');
        $fetchWithoutDomainBefore = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789');
        $deleteResp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/ghi789?domain=example.com');
        $fetchWithDomainAfter = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789?domain=example.com');
        $fetchWithoutDomainAfter = $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789');

        $this->assertEquals(self::STATUS_OK, $fetchWithDomainBefore->getStatusCode());
        $this->assertEquals(self::STATUS_OK, $fetchWithoutDomainBefore->getStatusCode());
        $this->assertEquals(self::STATUS_NO_CONTENT, $deleteResp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $fetchWithDomainAfter->getStatusCode());
        $this->assertEquals(self::STATUS_OK, $fetchWithoutDomainAfter->getStatusCode());
    }
}
