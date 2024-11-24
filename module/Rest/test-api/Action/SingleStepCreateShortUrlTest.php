<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class SingleStepCreateShortUrlTest extends ApiTestCase
{
    #[Test, DataProvider('provideFormats')]
    public function createsNewShortUrlWithExpectedResponse(string|null $format, string $expectedContentType): void
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

    #[Test]
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent(): void
    {
        $expectedDetail = 'Expected authentication to be provided in "apiKey" query param';

        $resp = $this->createShortUrl();
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_UNAUTHORIZED, $resp->getStatusCode());
        self::assertEquals(self::STATUS_UNAUTHORIZED, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/missing-authentication', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid authorization', $payload['title']);
    }

    private function createShortUrl(string|null $format = 'json', string|null $apiKey = null): ResponseInterface
    {
        $query = [
            'longUrl' => 'https://app.shlink.io',
            'apiKey' => $apiKey,
            'format' => $format,
        ];
        return $this->callApi(self::METHOD_GET, '/short-urls/shorten', [RequestOptions::QUERY => $query]);
    }
}
