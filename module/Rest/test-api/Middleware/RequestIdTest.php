<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class RequestIdTest extends ApiTestCase
{
    #[Test]
    public function generatesRequestId(): void
    {
        $response = $this->callApi('GET', '/health');
        self::assertTrue($response->hasHeader('X-Request-Id'));
    }

    #[Test]
    public function keepsProvidedRequestId(): void
    {
        $response = $this->callApi('GET', '/health', [
            RequestOptions::HEADERS => [
                'X-Request-Id' => 'foobar',
            ],
        ]);
        self::assertEquals('foobar', $response->hasHeader('X-Request-Id'));
    }
}
