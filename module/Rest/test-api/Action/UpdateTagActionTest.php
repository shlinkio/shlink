<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class UpdateTagActionTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideInvalidBody
     */
    public function notProvidingTagsReturnsBadRequest(array $body): void
    {
        $expectedDetail = 'Provided data is not valid';

        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => $body]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        $this->assertEquals('INVALID_ARGUMENT', $payload['type']);
        $this->assertEquals('INVALID_ARGUMENT', $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals('Invalid data', $payload['title']);
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
        $expectedDetail = 'Tag with name "invalid_tag" could not be found';

        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'invalid_tag',
            'newName' => 'foo',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('TAG_NOT_FOUND', $payload['type']);
        $this->assertEquals('TAG_NOT_FOUND', $payload['error']); // Deprecated
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals($expectedDetail, $payload['message']); // Deprecated
        $this->assertEquals('Tag not found', $payload['title']);
    }
}
