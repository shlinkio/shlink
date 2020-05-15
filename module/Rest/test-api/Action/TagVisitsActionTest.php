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

        $this->assertArrayHasKey('visits', $payload);
        $this->assertArrayHasKey('data', $payload['visits']);
        $this->assertCount($expectedVisitsAmount, $payload['visits']['data']);
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

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('TAG_NOT_FOUND', $payload['type']);
        $this->assertEquals('Tag with name "invalid_tag" could not be found', $payload['detail']);
        $this->assertEquals('Tag not found', $payload['title']);
    }
}
