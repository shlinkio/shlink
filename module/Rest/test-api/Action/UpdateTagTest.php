<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class UpdateTagTest extends ApiTestCase
{
    #[Test, DataProvider('provideInvalidBody')]
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

    public static function provideInvalidBody(): iterable
    {
        yield [[]];
        yield [['oldName' => 'foo']];
        yield [['newName' => 'foo']];
    }

    #[Test, DataProvider('provideTagNotFoundApiVersions')]
    public function tryingToRenameInvalidTagReturnsNotFound(string $version, string $expectedType): void
    {
        $expectedDetail = 'Tag with name "invalid_tag" could not be found';

        $resp = $this->callApiWithKey(self::METHOD_PUT, sprintf('/rest/v%s/tags', $version), [RequestOptions::JSON => [
            'oldName' => 'invalid_tag',
            'newName' => 'foo',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Tag not found', $payload['title']);
    }

    public static function provideTagNotFoundApiVersions(): iterable
    {
        yield 'version 1' => ['1', 'TAG_NOT_FOUND'];
        yield 'version 2' => ['2', 'TAG_NOT_FOUND'];
        yield 'version 3' => ['3', 'https://shlink.io/api/error/tag-not-found'];
    }

    #[Test, DataProvider('provideTagConflictsApiVersions')]
    public function errorIsThrownWhenTryingToRenameTagToAnotherTagName(string $version, string $expectedType): void
    {
        $expectedDetail = 'You cannot rename tag foo to bar, because it already exists';

        $resp = $this->callApiWithKey(self::METHOD_PUT, sprintf('/rest/v%s/tags', $version), [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'bar',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_CONFLICT, $resp->getStatusCode());
        self::assertEquals(self::STATUS_CONFLICT, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Tag conflict', $payload['title']);
    }

    public static function provideTagConflictsApiVersions(): iterable
    {
        yield 'version 1' => ['1', 'TAG_CONFLICT'];
        yield 'version 2' => ['2', 'TAG_CONFLICT'];
        yield 'version 3' => ['3', 'https://shlink.io/api/error/tag-conflict'];
    }

    #[Test]
    public function tagIsProperlyRenamedWhenRenamingToItself(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_PUT, '/tags', [RequestOptions::JSON => [
            'oldName' => 'foo',
            'newName' => 'foo',
        ]]);

        self::assertEquals(self::STATUS_NO_CONTENT, $resp->getStatusCode());
    }
}
