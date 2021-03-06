<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class DeleteTagsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideNonAdminApiKeys
     */
    public function anErrorIsReturnedWithNonAdminApiKeys(string $apiKey): void
    {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/tags', [
            RequestOptions::QUERY => ['tags' => ['foo']],
        ], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_FORBIDDEN, $resp->getStatusCode());
        self::assertEquals(self::STATUS_FORBIDDEN, $payload['status']);
        self::assertEquals('FORBIDDEN_OPERATION', $payload['type']);
        self::assertEquals('You are not allowed to delete tags', $payload['detail']);
        self::assertEquals('Forbidden tag operation', $payload['title']);
    }

    public function provideNonAdminApiKeys(): iterable
    {
        yield 'author' => ['author_api_key'];
        yield 'domain' => ['domain_api_key'];
    }
}
