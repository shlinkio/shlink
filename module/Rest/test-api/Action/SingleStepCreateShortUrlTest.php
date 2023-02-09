<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class SingleStepCreateShortUrlTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideFormats
     */
    public function createsNewShortUrlWithExpectedResponse(?string $format, string $expectedContentType): void
    {
        $resp = $this->createShortUrl($format, 'valid_api_key');

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertEquals($expectedContentType, $resp->getHeaderLine('Content-Type'));
    }

    public static function provideFormats(): iterable
    {
        yield 'txt format' => ['txt', 'text/plain'];
        yield 'json format' => ['json', 'application/json'];
        yield '<empty> format' => [null, 'application/json'];
    }

    /** @test */
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent(): void
    {
        $expectedDetail = 'Expected authentication to be provided in "apiKey" query param';

        $resp = $this->createShortUrl();
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals('INVALID_AUTHORIZATION', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid authorization', $payload['title']);
    }

    private function createShortUrl(?string $format = 'json', ?string $apiKey = null): ResponseInterface
    {
        $query = [
            'longUrl' => 'https://app.shlink.io',
            'apiKey' => $apiKey,
            'format' => $format,
        ];
        return $this->callApi(self::METHOD_GET, '/short-urls/shorten', [RequestOptions::QUERY => $query]);
    }
}
