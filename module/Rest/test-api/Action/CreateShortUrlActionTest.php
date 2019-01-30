<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use ShlinkioTest\Shlink\Common\ApiTest\ApiTestCase;

class CreateShortUrlActionTest extends ApiTestCase
{
    /**
     * @test
     */
    public function createsNewShortUrlWhenOnlyLongUrlIsProvided()
    {
        $expectedKeys = ['shortCode', 'shortUrl', 'longUrl', 'dateCreated', 'visitsCount', 'tags'];
        [$statusCode, $payload] = $this->createShortUrl();

        $this->assertEquals(self::STATUS_OK, $statusCode);
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    /**
     * @test
     */
    public function createsNewShortUrlWithCustomSlug()
    {
        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => 'my cool slug']);

        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals('my-cool-slug', $payload['shortCode']);
    }

    /**
     * @test
     */
    public function createsNewShortUrlWithTags()
    {
        [$statusCode, $payload] = $this->createShortUrl(['tags' => ['foo', 'bar', 'baz']]);

        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals(['foo', 'bar', 'baz'], $payload['tags']);
    }

    /**
     * @test
     * @dataProvider provideMaxVisits
     */
    public function createsNewShortUrlWithVisitsLimit(int $maxVisits)
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl(['maxVisits' => $maxVisits]);

        $this->assertEquals(self::STATUS_OK, $statusCode);

        // Last request to the short URL will return a 404, and the rest, a 302
        for ($i = 0; $i < $maxVisits; $i++) {
            $this->assertEquals(self::STATUS_FOUND, $this->callShortUrl($shortCode)->getStatusCode());
        }
        $lastResp = $this->callShortUrl($shortCode);
        $this->assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    public function provideMaxVisits(): array
    {
        return [
            [1],
            [5],
            [3],
        ];
    }

    /**
     * @test
     */
    public function createsShortUrlWithValidSince()
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validSince' => Chronos::now()->addDay()->toAtomString(),
        ]);

        $this->assertEquals(self::STATUS_OK, $statusCode);

        // Request to the short URL will return a 404 since ist' not valid yet
        $lastResp = $this->callShortUrl($shortCode);
        $this->assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    /**
     * @test
     */
    public function createsShortUrlWithValidUntil()
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validUntil' => Chronos::now()->subDay()->toAtomString(),
        ]);

        $this->assertEquals(self::STATUS_OK, $statusCode);

        // Request to the short URL will return a 404 since it's no longer valid
        $lastResp = $this->callShortUrl($shortCode);
        $this->assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    /**
     * @return array {
     *     @var int $statusCode
     *     @var array $payload
     * }
     */
    private function createShortUrl(array $body = []): array
    {
        $body['longUrl'] = 'https://app.shlink.io';
        $resp = $this->callApiWithKey(self::METHOD_POST, '/short-urls', [RequestOptions::JSON => $body]);
        $payload = $this->getJsonResponsePayload($resp);

        return [$resp->getStatusCode(), $payload];
    }
}
