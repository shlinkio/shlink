<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class DeleteTagsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideNonAdminApiKeys
     */
    public function anErrorIsReturnedWithNonAdminApiKeys(string $apiKey, string $version, string $expectedType): void
    {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, sprintf('/rest/v%s/tags', $version), [
            RequestOptions::QUERY => ['tags' => ['foo']],
        ], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_FORBIDDEN, $resp->getStatusCode());
        self::assertEquals(self::STATUS_FORBIDDEN, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals('You are not allowed to delete tags', $payload['detail']);
        self::assertEquals('Forbidden tag operation', $payload['title']);
    }

    public static function provideNonAdminApiKeys(): iterable
    {
        yield 'author' => ['author_api_key', '2', 'FORBIDDEN_OPERATION'];
        yield 'domain' => ['domain_api_key', '2', 'FORBIDDEN_OPERATION'];
        yield 'version 3' => ['domain_api_key', '3', 'https://shlink.io/api/error/forbidden-tag-operation'];
    }
}
