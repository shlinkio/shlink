<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function array_map;
use function range;
use function sprintf;
use function str_pad;

use const STR_PAD_BOTH;

class CreateShortUrlTest extends ApiTestCase
{
    #[Test]
    public function createsNewShortUrlWhenOnlyLongUrlIsProvided(): void
    {
        $expectedKeys = ['shortCode', 'shortUrl', 'longUrl', 'dateCreated', 'tags'];
        [$statusCode, $payload] = $this->createShortUrl();

        self::assertEquals(self::STATUS_OK, $statusCode);
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $payload);
        }
    }

    #[Test, DataProvider('provideValidLongUrls')]
    public function lonUrlSupportsDifferentTypesOfSchemas(string $longUrl): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['longUrl' => $longUrl]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals($longUrl, $payload['longUrl']);
    }

    public static function provideValidLongUrls(): iterable
    {
        yield 'mailto' => ['mailto:foo@example.com'];
        yield 'file' => ['file:///foo/bar'];
        yield 'https' => ['https://example.com'];
        yield 'deeplink' => ['shlink://some/path'];
    }

    #[Test]
    public function createsNewShortUrlWithCustomSlug(): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => 'my cool slug']);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals('my-cool-slug', $payload['shortCode']);
    }

    #[Test, DataProvider('provideConflictingSlugs')]
    public function failsToCreateShortUrlWithDuplicatedSlug(string $slug, string|null $domain): void
    {
        $suffix = $domain === null ? '' : sprintf(' for domain "%s"', $domain);
        $detail = sprintf('Provided slug "%s" is already in use%s.', $slug, $suffix);

        [$statusCode, $payload] = $this->createShortUrl(['customSlug' => $slug, 'domain' => $domain]);

        self::assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals($detail, $payload['detail']);
        self::assertEquals('https://shlink.io/api/error/non-unique-slug', $payload['type']);
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
        yield ['1', 'https://shlink.io/api/error/non-unique-slug'];
        yield ['2', 'https://shlink.io/api/error/non-unique-slug'];
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
        return array_map(static fn (int $i) => [$i], range(10, 15));
    }

    #[Test]
    public function createsShortUrlWithValidSince(): void
    {
        [$statusCode, ['shortCode' => $shortCode]] = $this->createShortUrl([
            'validSince' => Chronos::now()->addDays(1)->toAtomString(),
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
            'validUntil' => Chronos::now()->subDays(1)->toAtomString(),
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
    public function returnsErrorWhenRequestingReturnExistingButCustomSlugIsInUse(
        string $slug,
        string|null $domain,
    ): void {
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

    #[Test, DataProvider('provideInvalidArgumentApiVersions')]
    public function failsToCreateShortUrlWithoutLongUrl(array $payload, string $version): void
    {
        $resp = $this->callApiWithKey(
            self::METHOD_POST,
            sprintf('/rest/v%s/short-urls', $version),
            [RequestOptions::JSON => $payload],
        );
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/invalid-data', $payload['type']);
        self::assertEquals('Provided data is not valid', $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
    }

    public static function provideInvalidArgumentApiVersions(): iterable
    {
        yield 'missing long url v2' => [[], '2'];
        yield 'missing long url v3' => [[], '3'];
        yield 'empty long url v2' => [['longUrl' => null], '2'];
        yield 'empty long url v3' => [['longUrl' => '  '], '3'];
        yield 'missing url schema v2' => [['longUrl' => 'foo.com'], '2'];
        yield 'missing url schema v3' => [['longUrl' => 'foo.com'], '3'];
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
    public function apiKeyDomainIsEnforced(string|null $providedDomain): void
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
    public function titleIsIgnoredIfLongUrlTimesOut(): void
    {
        [$statusCode, $payload] = $this->createShortUrl([
            'longUrl' => 'http://127.0.0.1:9999/api-tests/long-url-with-timeout',
        ]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertNull($payload['title']);
    }

    #[Test, DataProvider('provideTitles')]
    public function titleIsCroppedIfTooLong(string $title, string $expectedTitle): void
    {
        [$statusCode, ['title' => $returnedTitle]] = $this->createShortUrl(['title' => $title]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertEquals($expectedTitle, $returnedTitle);
    }

    public static function provideTitles(): iterable
    {
        yield ['foo', 'foo'];
        yield [str_pad('bar', 600, ' ', STR_PAD_BOTH), 'bar'];
        yield [str_pad('', 511, 'a'), str_pad('', 511, 'a')];
        yield [str_pad('', 512, 'b'), str_pad('', 512, 'b')];
        yield [str_pad('', 513, 'c'), str_pad('', 512, 'c')];
        yield [str_pad('', 600, 'd'), str_pad('', 512, 'd')];
        yield [str_pad('', 800, 'e'), str_pad('', 512, 'e')];
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith(['my-custom-slug'])]
    public function prefixCanBeSet(string|null $customSlug): void
    {
        [$statusCode, $payload] = $this->createShortUrl([
            'longUrl' => 'https://github.com/shlinkio/shlink/issues/1557',
            'pathPrefix' => 'foo/b  ar-baz',
            'customSlug' => $customSlug,
        ]);

        self::assertEquals(self::STATUS_OK, $statusCode);
        self::assertStringStartsWith('foo-b--ar-baz', $payload['shortCode']);
    }



    #[Test]
    #[TestWith(['localhost:80000'])]
    #[TestWith(['127.0.0.1'])]
    #[TestWith(['???/&%$&'])]
    public function failsToCreateShortUrlWithInvalidDomain(string $domain): void
    {
        [$statusCode, $payload] = $this->createShortUrl(['domain' => $domain]);

        self::assertEquals(self::STATUS_BAD_REQUEST, $statusCode);
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals('https://shlink.io/api/error/invalid-data', $payload['type']);
        self::assertEquals('Provided data is not valid', $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
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
