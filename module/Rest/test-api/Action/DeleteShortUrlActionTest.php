<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class DeleteShortUrlActionTest extends ApiTestCase
{
    /** @test */
    public function notFoundErrorIsReturnWhenDeletingInvalidUrl(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/invalid');
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals('INVALID_SHORTCODE', $error);
    }

    /** @test */
    public function unprocessableEntityIsReturnedWhenTryingToDeleteUrlWithTooManyVisits(): void
    {
        // Generate visits first
        for ($i = 0; $i < 20; $i++) {
            $this->assertEquals(self::STATUS_FOUND, $this->callShortUrl('abc123')->getStatusCode());
        }

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/abc123');
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_UNPROCESSABLE_ENTITY, $resp->getStatusCode());
        $this->assertEquals('INVALID_SHORTCODE_DELETION', $error);
    }
}
