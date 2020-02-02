<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

class EditShortUrlTagsActionTest extends ApiTestCase
{
    use NotFoundUrlHelpersTrait;

    /** @test */
    public function notProvidingTagsReturnsBadRequest(): void
    {
        $expectedDetail = 'Provided data is not valid';

        $resp = $this->callApiWithKey(self::METHOD_PUT, '/short-urls/abc123/tags', [RequestOptions::JSON => []]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        $this->assertEquals('INVALID_ARGUMENT', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Invalid data', $payload['title']);
    }

    /**
     * @test
     * @dataProvider provideInvalidUrls
     */
    public function providingInvalidShortCodeReturnsBadRequest(
        string $shortCode,
        ?string $domain,
        string $expectedDetail
    ): void {
        $url = $this->buildShortUrlPath($shortCode, $domain, '/tags');
        $resp = $this->callApiWithKey(self::METHOD_PUT, $url, [RequestOptions::JSON => [
            'tags' => ['foo', 'bar'],
        ]]);
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
    public function tagsAreSetOnProperShortUrlBasedOnProvidedDomain(): void
    {
        $urlWithoutDomain = '/short-urls/ghi789/tags';
        $urlWithDomain = $urlWithoutDomain . '?domain=example.com';

        $setTagsWithDomain = $this->callApiWithKey(self::METHOD_PUT, $urlWithDomain, [RequestOptions::JSON => [
            'tags' => ['foo', 'bar'],
        ]]);
        $fetchWithoutDomain = $this->getJsonResponsePayload(
            $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789'),
        );
        $fetchWithDomain = $this->getJsonResponsePayload(
            $this->callApiWithKey(self::METHOD_GET, '/short-urls/ghi789?domain=example.com'),
        );

        $this->assertEquals(self::STATUS_OK, $setTagsWithDomain->getStatusCode());
        $this->assertEquals([], $fetchWithoutDomain['tags']);
        $this->assertEquals(['bar', 'foo'], $fetchWithDomain['tags']);
    }
}
