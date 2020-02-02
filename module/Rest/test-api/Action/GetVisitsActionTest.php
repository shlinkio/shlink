<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function GuzzleHttp\Psr7\build_query;
use function sprintf;

class GetVisitsActionTest extends ApiTestCase
{
    /** @test */
    public function tryingToGetVisitsForInvalidUrlReturnsNotFoundError(): void
    {
        $expectedDetail = 'No URL found with short code "invalid"';

        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls/invalid/visits');
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Short URL not found', $payload['title']);
        $this->assertEquals('invalid', $payload['shortCode']);
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function properVisitsAreReturnedWhenDomainIsProvided(?string $domain, int $expectedAmountOfVisits): void
    {
        $shortCode = 'ghi789';
        $url = new Uri(sprintf('/short-urls/%s/visits', $shortCode));

        if ($domain !== null) {
            $url = $url->withQuery(build_query(['domain' => $domain]));
        }

        $resp = $this->callApiWithKey(self::METHOD_GET, (string) $url);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals($expectedAmountOfVisits, $payload['visits']['pagination']['totalItems'] ?? -1);
        $this->assertCount($expectedAmountOfVisits, $payload['visits']['data'] ?? []);
    }

    public function provideDomains(): iterable
    {
        yield 'domain' => ['example.com', 0];
        yield 'no domain' => [null, 2];
    }
}
