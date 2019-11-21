<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class UpdateTagActionTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideInvalidBody
     */
    public function notProvidingTagsReturnsBadRequest(array $body): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => $body]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals(RestUtils::INVALID_ARGUMENT_ERROR, $error);
    }

    public function provideInvalidBody(): iterable
    {
        yield [[]];
        yield [['oldName' => 'foo']];
        yield [['newName' => 'foo']];
    }

    /** @test */
    public function tryingToRenameInvalidTagReturnsNotFound(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'invalid_tag',
            'newName' => 'foo',
        ]]);
        ['error' => $error] = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(RestUtils::NOT_FOUND_ERROR, $error);
    }
}
