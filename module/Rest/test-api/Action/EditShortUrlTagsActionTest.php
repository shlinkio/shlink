<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class EditShortUrlTagsActionTest extends ApiTestCase
{
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

    /** @test */
    public function providingInvalidShortCodeReturnsBadRequest(): void
    {
        $expectedDetail = 'No URL found with short code "invalid"';

        $resp = $this->callApiWithKey(self::METHOD_PUT, '/short-urls/invalid/tags', [RequestOptions::JSON => [
            'tags' => ['foo', 'bar'],
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Short URL not found', $payload['title']);
        $this->assertEquals('invalid', $payload['shortCode']);
    }
}
