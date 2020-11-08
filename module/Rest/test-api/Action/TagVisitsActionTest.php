<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class TagVisitsActionTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideTags
     */
    public function expectedVisitsAreReturned(string $tag, int $expectedVisitsAmount): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, sprintf('/tags/%s/visits', $tag));
        $payload = $this->getJsonResponsePayload($resp);

        self::assertArrayHasKey('visits', $payload);
        self::assertArrayHasKey('data', $payload['visits']);
        self::assertCount($expectedVisitsAmount, $payload['visits']['data']);
    }

    public function provideTags(): iterable
    {
        yield 'foo' => ['foo', 5];
        yield 'bar' => ['bar', 2];
        yield 'baz' => ['baz', 0];
    }

    /** @test */
    public function notFoundErrorIsReturnedForInvalidTags(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/tags/invalid_tag/visits');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('TAG_NOT_FOUND', $payload['type']);
        self::assertEquals('Tag with name "invalid_tag" could not be found', $payload['detail']);
        self::assertEquals('Tag not found', $payload['title']);
    }
}
