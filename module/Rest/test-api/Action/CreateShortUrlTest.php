<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function Functional\map;
use function range;
use function sprintf;

class CreateShortUrlTest extends ApiTestCase
{
    #[Test]
    public function createsNewShortUrlWhenOnlyLongUrlIsProvided(): void
    {
        $expectedKeys = ['shortCode', 'shortUrl', 'longUrl', 'dateCreated', 'visitsCount', 'tags'];
        [$statusCode, $payload] = $this->createShortUrl();

        self::assertEquals(self::STATUS_OK, $statusCode);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $payload);
        }
    }

    #[Test]
    public function createsNewShortUrlWithCustomSlug(): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => 'my cool slug']);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals('my-cool-slug', $payload['shortCode']);
    }

    #[Test, DataProvider('provideConflictingSlugs')]
    public function failsToCreateShortUrlWithDuplicatedSlug(string $slug, ?string $domain): void
    {
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $detail = sprintf('Provided slug "%s" is already in use%s.', $slug, $suffix);

        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => $slug, 'domain' => $domain]);

        self::assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals($detail, $payload['detail']);
        self::assertEquals('INVALID_SLUG', $payload['type']);
        self::assertEquals('Invalid custom slug', $payload['title']);
        self::assertEquals($slug, $payload['customSlug']);

        if ($domain !== null) {
            self::assertEquals($domain, $payload['domain']);
        } else {
            self::assertArrayNotHasKey('domain', $payload);
        }
    }

    #[Test, DataProvider('provideDuplicatedSlugApiVersions')]
    public function expectedTypeIsReturnedForConflictingSlugBasedOnApiVersion(
        string $version,
        string $expectedType,
    ): void {
        [, $payload] = $this->createShortUrl(['customSlug' => 'custom'], version: $version);
        self::assertEquals($expectedType, $payload['type']);
    }

    public static function provideDuplicatedSlugApiVersions(): iterable
    {
        yield ['1', 'INVALID_SLUG'];
        yield ['2', 'INVALID_SLUG'];
        yield ['3', 'https://shlink.io/api/error/non-unique-slug'];
    }

    #[Test, DataProvider('provideTags')]
    public function createsNewShortUrlWithTags(array $providedTags, array $expectedTags): void
    {
        [$statusCode, ['tags' => $tags]] = $this->createShortUrl(['tags' => $providedTags]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals($expectedTags, $tags);
    }

    public static function provideTags(): iterable
    {
        yield 'simple tags' => [$simpleTags = ['foo', 'bar', 'baz'], $simpleTags];
        yield 'tags with spaces' => [['fo o', '  bar', 'b az'], ['fo-o', 'bar', 'b-az']];
        yield 'tags with special chars' => [['UUU', 'Aäa'], ['uuu', 'aäa']];
    }

    #[Test, DataProvider('provideMaxVisits')]
    public function createsNewShortUrlWithVisitsLimit(int $maxVisits): void
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl(['maxVisits' => $maxVisits]);

        self::assertEquals(self::STATUS_OK, $statusCode);

        // Last request to the short URL will return a 404, and the rest, a 302
        for ($i = 0; $i < $maxVisits; $i++) {
            self::assertEquals(self::STATUS_FOUND, $this->callShortUrl($shortCode)->getStatusCode());
        }
        $lastResp = $this->callShortUrl($shortCode);
        self::assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    public static function provideMaxVisits(): array
    {
        return map(range(10, 15), fn(int $i) => [$i]);
    }

    #[Test]
    public function createsShortUrlWithValidSince(): void
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validSince' => Chronos::now()->addDay()->toAtomString(),
        ]);

        self::assertEquals(self::STATUS_OK, $statusCode);

        // Request to the short URL will return a 404 since it's not valid yet
        $lastResp = $this->callShortUrl($shortCode);
        self::assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    #[Test]
    public function createsShortUrlWithValidUntil(): void
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validUntil' => Chronos::now()->subDay()->toAtomString(),
        ]);

        self::assertEquals(self::STATUS_OK, $statusCode);

        // Request to the short URL will return a 404 since it's no longer valid
        $lastResp = $this->callShortUrl($shortCode);
        self::assertEquals(self::STATUS_NOT_FOUND, $lastResp->getStatusCode());
    }

    #[Test, DataProvider('provideMatchingBodies')]
    public function returnsAnExistingShortUrlWhenRequested(array $body): void
    {
        [$firstStatusCode, ['shortCode' => $firstShortCode]] = $this->createShortUrl($body);

        $body['findIfExists'] = true;
        [$secondStatusCode, ['shortCode' => $secondShortCode]] = $this->createShortUrl($body);

        self::assertEquals(self::STATUS_OK, $firstStatusCode);
        self::assertEquals(self::STATUS_OK, $secondStatusCode);
        self::assertEquals($firstShortCode, $secondShortCode);
    }

    public static function provideMatchingBodies(): iterable
    {
        $longUrl = 'https://www.alejandrocelaya.com';

        yield 'only long URL' => [['longUrl' => $longUrl]];
        yield 'long URL and tags' => [['longUrl' => $longUrl, 'tags' => ['boo', 'far']]];
        yield 'long URL and custom slug' => [['longUrl' => $longUrl, 'customSlug' => 'my cool slug']];
        yield 'several params' => [
            [
                'longUrl' => $longUrl,
                'tags' => ['boo', 'far'],
                'validSince' => Chronos::now()->toAtomString(),
                'maxVisits' => 7,
            ],
        ];
    }

    #[Test, DataProvider('provideConflictingSlugs')]
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

        self::assertEquals(self::STATUS_OK, $firstStatusCode);
        self::assertEquals(self::STATUS_BAD_REQUEST, $secondStatusCode);
    }

    public static function provideConflictingSlugs(): iterable
    {
        yield 'without domain' => ['custom', null];
        yield 'with domain' => ['custom-with-domain', 'some-domain.com'];
    }

    #[Test]
    public function createsNewShortUrlIfRequestedToFindButThereIsNoMatch(): void
    {
        [$firstStatusCode, ['shortCode' => $firstShortCode]] = $this->createShortUrl([
            'longUrl' => 'https://www.alejandrocelaya.com',
        ]);
        [$secondStatusCode, ['shortCode' => $secondShortCode]] = $this->createShortUrl([
            'longUrl' => 'https://www.alejandrocelaya.com/projects',
            'findIfExists' => true,
        ]);

        self::assertEquals(self::STATUS_OK, $firstStatusCode);
        self::assertEquals(self::STATUS_OK, $secondStatusCode);
        self::assertNotEquals($firstShortCode, $secondShortCode);
    }

    #[Test, DataProvider('provideIdn')]
    public function createsNewShortUrlWithInternationalizedDomainName(string $longUrl): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['longUrl' => $longUrl]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals($payload['longUrl'], $longUrl);
    }

    public static function provideIdn(): iterable
    {
        yield ['http://tést.shlink.io']; // Redirects to https://shlink.io
        yield ['http://test.shlink.io']; // Redirects to http://tést.shlink.io
        yield ['http://téstb.shlink.io']; // Redirects to http://tést.shlink.io
    }

    #[Test, DataProvider('provideInvalidUrls')]
    public function failsToCreateShortUrlWithInvalidLongUrl(string $url, string $version, string $expectedType): void
    {
        $expectedDetail = sprintf('Provided URL %s is invalid. Try with a different one.', $url);

        [$statusCode, $payload] = $this->createShortUrl(['longUrl' => $url, 'validateUrl' => true], version: $version);

        self::assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid URL', $payload['title']);
        self::assertEquals($url, $payload['url']);
    }

    public static function provideInvalidUrls(): iterable
    {
        yield 'API version 2' => ['https://this-has-to-be-invalid.com', '2', 'INVALID_URL'];
        yield 'API version 3' => ['https://this-has-to-be-invalid.com', '3', 'https://shlink.io/api/error/invalid-url'];
    }

    #[Test, DataProvider('provideInvalidArgumentApiVersions')]
    public function failsToCreateShortUrlWithoutLongUrl(array $payload, string $version, string $expectedType): void
    {
        $resp = $this->callApiWithKey(
            self::METHOD_POST,
            sprintf('/rest/v%s/short-urls', $version),
            [RequestOptions::JSON => $payload],
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals($expectedType, $payload['type']);
        self::assertEquals('Provided data is not valid', $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
    }

    public static function provideInvalidArgumentApiVersions(): iterable
    {
        yield 'missing long url v2' => [[], '2', 'INVALID_ARGUMENT'];
        yield 'missing long url v3' => [[], '3', 'https://shlink.io/api/error/invalid-data'];
        yield 'empty long url v2' => [['longUrl' => null], '2', 'INVALID_ARGUMENT'];
        yield 'empty long url v3' => [['longUrl' => '  '], '3', 'https://shlink.io/api/error/invalid-data'];
        yield 'empty device long url v2' => [[
            'longUrl' => 'foo',
            'deviceLongUrls' => [
                'android' => null,
            ],
        ], '2', 'INVALID_ARGUMENT'];
        yield 'empty device long url v3' => [[
            'longUrl' => 'foo',
            'deviceLongUrls' => [
                'ios' => '  ',
            ],
        ], '3', 'https://shlink.io/api/error/invalid-data'];
    }

    #[Test]
    public function defaultDomainIsDroppedIfProvided(): void
    {
        [$createStatusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'longUrl' => 'https://www.alejandrocelaya.com',
            'domain' => 's.test',
        ]);
        $getResp = $this->callApiWithKey(self::METHOD_GET, '/short-urls/' . $shortCode);
        $payload = $this->getJsonResponsePayload($getResp);

        self::assertEquals(self::STATUS_OK, $createStatusCode);
        self::assertEquals(self::STATUS_OK, $getResp->getStatusCode());
        self::assertArrayHasKey('domain', $payload);
        self::assertNull($payload['domain']);
    }

    #[Test, DataProvider('provideDomains')]
    public function apiKeyDomainIsEnforced(?string $providedDomain): void
    {
        [$statusCode, ['domain' => $returnedDomain]] = $this->createShortUrl(
            ['domain' => $providedDomain],
            'domain_api_key',
        );

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals('example.com', $returnedDomain);
    }

    public static function provideDomains(): iterable
    {
        yield 'no domain' => [null];
        yield 'invalid domain' => ['this-will-be-overwritten.com'];
        yield 'example domain' => ['example.com'];
    }

    #[Test, DataProvider('provideTwitterUrls')]
    public function urlsWithBothProtectionCanBeShortenedWithUrlValidationEnabled(string $longUrl): void
    {
        [$statusCode] = $this->createShortUrl(['longUrl' => $longUrl, 'validateUrl' => true]);
        self::assertEquals(self::STATUS_OK, $statusCode);
    }

    public static function provideTwitterUrls(): iterable
    {
        yield ['https://twitter.com/shlinkio'];
        yield ['https://mobile.twitter.com/shlinkio'];
        yield ['https://twitter.com/shlinkio/status/1360637738421268481'];
        yield ['https://mobile.twitter.com/shlinkio/status/1360637738421268481'];
    }

    #[Test]
    public function canCreateShortUrlsWithEmojis(): void
    {
        [$statusCode, $payload] = $this->createShortUrl([
            'longUrl' => 'https://emojipedia.org/fire/',
            'title' => '🔥🔥🔥',
            'customSlug' => '🦣🦣🦣',
        ]);
        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals('🔥🔥🔥', $payload['title']);
        self::assertEquals('🦣🦣🦣', $payload['shortCode']);
        self::assertEquals('http://s.test/🦣🦣🦣', $payload['shortUrl']);
    }

    #[Test]
    public function canCreateShortUrlsWithDeviceLongUrls(): void
    {
        [$statusCode, $payload] = $this->createShortUrl([
            'longUrl' => 'https://github.com/shlinkio/shlink/issues/1557',
            'deviceLongUrls' => [
                'ios' => 'https://github.com/shlinkio/shlink/ios',
                'android' => 'https://github.com/shlinkio/shlink/android',
            ],
        ]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals('https://github.com/shlinkio/shlink/ios', $payload['deviceLongUrls']['ios'] ?? null);
        self::assertEquals('https://github.com/shlinkio/shlink/android', $payload['deviceLongUrls']['android'] ?? null);
    }

    /**
     * @return array{int, array}
     */
    private function createShortUrl(array $body = [], string $apiKey = 'valid_api_key', string $version = '2'): array
    {
        if (! isset($body['longUrl'])) {
            $body['longUrl'] = 'https://app.shlink.io';
        }
        $resp = $this->callApiWithKey(
            self::METHOD_POST,
            sprintf('/rest/v%s/short-urls', $version),
            [RequestOptions::JSON => $body],
            $apiKey,
        );
        $payload = $this->getJsonResponsePayload($resp);

        return [$resp->getStatusCode(), $payload];
    }
}
