<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class DeleteShortUrlVisitsTest extends ApiTestCase
{
    #[Test]
    public function deletesVisitsForShortUrlWithoutAffectingTheRest(): void
    {
        self::assertEquals(7, $this->getTotalVisits());
        self::assertEquals(3, $this->getOrphanVisits());

        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/abc123/visits');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals(3, $payload['deletedVisits']);
        self::assertEquals(4, $this->getTotalVisits()); // This verifies that other visits have not been affected
        self::assertEquals(3, $this->getOrphanVisits()); // This verifies that orphan visits have not been affected
    }

    private function getTotalVisits(): int
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/non-orphan');
        $payload = $this->getJsonResponsePayload($resp);

        return $payload['visits']['pagination']['totalItems'];
    }

    private function getOrphanVisits(): int
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/visits/orphan');
        $payload = $this->getJsonResponsePayload($resp);

        return $payload['visits']['pagination']['totalItems'];
    }

    #[Test, DataProvider('provideInvalidShortUrls')]
    public function returnsErrorForInvalidShortUrls(string $uri, array $options, string $expectedError): void
    {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/rest/v3' . $uri, $options);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(404, $resp->getStatusCode());
        self::assertEquals($expectedError, $payload['detail']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
    }

    public static function provideInvalidShortUrls(): iterable
    {
        yield 'not exists' => [
            '/short-urls/does-not-exist/visits',
            [],
            'No URL found with short code "does-not-exist"',
        ];
        yield 'needs domain' => [
            '/short-urls/custom-with-domain/visits',
            [],
            'No URL found with short code "custom-with-domain"',
        ];
        yield 'invalid domain' => [
            '/short-urls/abc123/visits',
            [RequestOptions::QUERY => ['domain' => 'ff.test']],
            'No URL found with short code "abc123" for domain "ff.test"',
        ];
        yield 'wrong domain' => [
            '/short-urls/custom-with-domain/visits',
            [RequestOptions::QUERY => ['domain' => 'ff.test']],
            'No URL found with short code "custom-with-domain" for domain "ff.test"',
        ];
    }

    #[Test]
    public function cannotDeleteVisitsForShortUrlWithWrongApiKeyPermissions(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_DELETE, '/short-urls/abc123/visits', [], 'domain_api_key');
        self::assertEquals(404, $resp->getStatusCode());
    }
}
