<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function Functional\map;
use function range;
use function sprintf;

class CreateShortUrlActionTest extends ApiTestCase
{
    /** @test */
    public function createsNewShortUrlWhenOnlyLongUrlIsProvided(): void
    {
        $expectedKeys = ['shortCode', 'shortUrl', 'longUrl', 'dateCreated', 'visitsCount', 'tags'];
        [$statusCode, $payload] = $this->createShortUrl();

        $this->assertEquals(self::STATUS_OK, $statusCode);
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $payload);
        }
    }

    /** @test */
    public function createsNewShortUrlWithCustomSlug(): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => 'my cool slug']);

        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals('my-cool-slug', $payload['shortCode']);
    }

    /**
     * @test
     * @dataProvider provideConflictingSlugs
     */
    public function failsToCreateShortUrlWithDuplicatedSlug(string $slug, ?string $domain): void
    {
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $detail = sprintf('Provided slug "%s" is already in use%s.', $slug, $suffix);

        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => $slug, 'domain' => $domain]);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        $this->assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        $this->assertEquals($detail, $payload['detail']);
        $this->assertEquals('INVALID_SLUG', $payload['type']);
        $this->assertEquals('Invalid custom slug', $payload['title']);
        $this->assertEquals($slug, $payload['customSlug']);

        if ($domain !== null) {
            $this->assertEquals($domain, $payload['domain']);
        } else {
            $this->assertArrayNotHasKey('domain', $payload);
        }
    }

    /** @test */
    public function createsNewShortUrlWithTags(): void
    {
        [$statusCode, ['tags' => $tags]] = $this->createShortUrl(['tags' => ['foo', 'bar', 'baz']]);

        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals(['foo', 'bar', 'baz'], $tags);
    }

    /**
     * @test
     * @dataProvider provideMaxVisits
     */
    public function createsNewShortUrlWithVisitsLimit(int $maxVisits): void
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
        return map(range(10, 15), fn (int $i) => [$i]);
    }

    /** @test */
    public function createsShortUrlWithValidSince(): void
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validSince' => Chronos::now()->addDay()->toAtomString(),
        ]);

        $this->assertEquals(self::STATUS_OK, $statusCode);

        // Request to the short URL will return a 404 since it's not valid yet
        $lastResp = $this->callShortUrl($shortCode);
        $this->assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    /** @test */
    public function createsShortUrlWithValidUntil(): void
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
     * @test
     * @dataProvider provideMatchingBodies
     */
    public function returnsAnExistingShortUrlWhenRequested(array $body): void
    {
        [$firstStatusCode, ['shortCode' => $firstShortCode]] = $this->createShortUrl($body);

        $body['findIfExists'] = true;
        [$secondStatusCode, ['shortCode' => $secondShortCode]] = $this->createShortUrl($body);

        $this->assertEquals(self::STATUS_OK, $firstStatusCode);
        $this->assertEquals(self::STATUS_OK, $secondStatusCode);
        $this->assertEquals($firstShortCode, $secondShortCode);
    }

    public function provideMatchingBodies(): iterable
    {
        $longUrl = 'https://www.alejandrocelaya.com';

        yield 'only long URL' => [['longUrl' => $longUrl]];
        yield 'long URL and tags' => [['longUrl' => $longUrl, 'tags' => ['boo', 'far']]];
        yield 'long URL and custom slug' => [['longUrl' => $longUrl, 'customSlug' => 'my cool slug']];
        yield 'several params' => [[
            'longUrl' => $longUrl,
            'tags' => ['boo', 'far'],
            'validSince' => Chronos::now()->toAtomString(),
            'maxVisits' => 7,
        ]];
    }

    /**
     * @test
     * @dataProvider provideConflictingSlugs
     */
    public function returnsErrorWhenRequestingReturnExistingButCustomSlugIsInUse(string $slug, ?string $domain): void
    {
        $longUrl = 'https://www.alejandrocelaya.com';

        [$firstStatusCode] = $this->createShortUrl(['longUrl' => $longUrl]);
        [$secondStatusCode] = $this->createShortUrl([
            'longUrl' => $longUrl,
            'customSlug' => $slug,
            'findIfExists' => true,
            'domain' => $domain,
        ]);

        $this->assertEquals(self::STATUS_OK, $firstStatusCode);
        $this->assertEquals(self::STATUS_BAD_REQUEST, $secondStatusCode);
    }

    public function provideConflictingSlugs(): iterable
    {
        yield 'without domain' => ['custom', null];
        yield 'with domain' => ['custom-with-domain', 'some-domain.com'];
    }

    /** @test */
    public function createsNewShortUrlIfRequestedToFindButThereIsNoMatch(): void
    {
        [$firstStatusCode, ['shortCode' => $firstShortCode]] = $this->createShortUrl([
            'longUrl' => 'https://www.alejandrocelaya.com',
        ]);
        [$secondStatusCode, ['shortCode' => $secondShortCode]] = $this->createShortUrl([
            'longUrl' => 'https://www.alejandrocelaya.com/projects',
            'findIfExists' => true,
        ]);

        $this->assertEquals(self::STATUS_OK, $firstStatusCode);
        $this->assertEquals(self::STATUS_OK, $secondStatusCode);
        $this->assertNotEquals($firstShortCode, $secondShortCode);
    }

    /**
     * @test
     * @dataProvider provideIdn
     */
    public function createsNewShortUrlWithInternationalizedDomainName(string $longUrl): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['longUrl' => $longUrl]);

        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals($payload['longUrl'], $longUrl);
    }

    public function provideIdn(): iterable
    {
        yield ['http://tést.shlink.io']; // Redirects to https://shlink.io
        yield ['http://test.shlink.io']; // Redirects to http://tést.shlink.io
        yield ['http://téstb.shlink.io']; // Redirects to http://tést.shlink.io
    }

    /** @test */
    public function failsToCreateShortUrlWithInvalidLongUrl(): void
    {
        $url = 'https://this-has-to-be-invalid.com';
        $expectedDetail = sprintf('Provided URL %s is invalid. Try with a different one.', $url);

        [$statusCode, $payload] = $this->createShortUrl(['longUrl' => $url]);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        $this->assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        $this->assertEquals('INVALID_URL', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Invalid URL', $payload['title']);
        $this->assertEquals($url, $payload['url']);
    }

    /**
     * @return array {
     *     @var int $statusCode
     *     @var array $payload
     * }
     */
    private function createShortUrl(array $body = []): array
    {
        if (! isset($body['longUrl'])) {
            $body['longUrl'] = 'https://app.shlink.io';
        }
        $resp = $this->callApiWithKey(self::METHOD_POST, '/short-urls', [RequestOptions::JSON => $body]);
        $payload = $this->getJsonResponsePayload($resp);

        return [$resp->getStatusCode(), $payload];
    }
}
