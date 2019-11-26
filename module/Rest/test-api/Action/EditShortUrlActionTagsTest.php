<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class EditShortUrlActionTagsTest extends ApiTestCase
{
    /** @test */
    public function notProvidingTagsReturnsBadRequest(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/short-urls/abc123/tags', [RequestOptions::JSON => []]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals('INVALID_ARGUMENT', $error);
    }

    /** @test */
    public function providingInvalidShortCodeReturnsBadRequest(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/short-urls/invalid/tags', [RequestOptions::JSON => [
            'tags' => ['foo', 'bar'],
        ]]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals('INVALID_SHORTCODE', $error);
    }
}
