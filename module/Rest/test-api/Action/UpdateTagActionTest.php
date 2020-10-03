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

        self::assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals('INVALID_ARGUMENT', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
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

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('TAG_NOT_FOUND', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Tag not found', $payload['title']);
    }

    /** @test */
    public function errorIsThrownWhenTryingToRenameTagToAnotherTagName(): void
    {
        $expectedDetail = 'You cannot rename tag foo to bar, because it already exists';

        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'bar',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_CONFLICT, $resp->getStatusCode());
        self::assertEquals(self::STATUS_CONFLICT, $payload['status']);
        self::assertEquals('TAG_CONFLICT', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Tag conflict', $payload['title']);
    }

    /** @test */
    public function tagIsProperlyRenamedWhenRenamingToItself(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'foo',
        ]]);

        self::assertEquals(self::STATUS_NO_CONTENT, $resp->getStatusCode());
    }
}
