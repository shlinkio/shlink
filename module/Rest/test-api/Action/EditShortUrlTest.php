<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\RequestOptions;
use Laminas\Diactoros\Uri;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

use function sprintf;

class EditShortUrlTest extends ApiTestCase
{
    use ArraySubsetAsserts;
    use NotFoundUrlHelpersTrait;

    /**
     * @test
     * @dataProvider provideMeta
     */
    public function metadataCanBeReset(array $meta): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);
        $resetMeta = [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ];

        $editWithProvidedMeta = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => $meta]);
        $metaAfterEditing = $this->findShortUrlMetaByShortCode($shortCode);

        $editWithResetMeta = $this->callApiWithKey(self::METHOD_PATCH, $url, [
            RequestOptions::JSON => $resetMeta,
        ]);
        $metaAfterResetting = $this->findShortUrlMetaByShortCode($shortCode);

        self::assertEquals(self::STATUS_OK, $editWithProvidedMeta->getStatusCode());
        self::assertEquals(self::STATUS_OK, $editWithResetMeta->getStatusCode());
        self::assertEquals($resetMeta, $metaAfterResetting);
        self::assertArraySubset($meta, $metaAfterEditing);
    }

    public function provideMeta(): iterable
    {
        $now = Chronos::now();

        yield [['validSince' => $now->addMonth()->toAtomString()]];
        yield [['validUntil' => $now->subMonth()->toAtomString()]];
        yield [['maxVisits' => 20]];
        yield [['validUntil' => $now->addYear()->toAtomString(), 'maxVisits' => 100]];
        yield [[
            'validSince' => $now->subYear()->toAtomString(),
            'validUntil' => $now->addYear()->toAtomString(),
            'maxVisits' => 100,
        ]];
    }

    private function findShortUrlMetaByShortCode(string $shortCode): ?array
    {
        $matchingShortUrl = $this->getJsonResponsePayload(
            $this->callApiWithKey(self::METHOD_GET, '/short-urls/' . $shortCode),
        );

        return $matchingShortUrl['meta'] ?? null;
    }

    /**
     * @test
     * @dataProvider provideLongUrls
     */
    public function longUrlCanBeEditedIfItIsValid(string $longUrl, int $expectedStatus, ?string $expectedError): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);

        $resp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => [
            'longUrl' => $longUrl,
            'validateUrl' => true,
        ]]);

        self::assertEquals($expectedStatus, $resp->getStatusCode());
        if ($expectedError !== null) {
            $payload = $this->getJsonResponsePayload($resp);
            self::assertEquals($expectedError, $payload['type']);
        }
    }

    public function provideLongUrls(): iterable
    {
        yield 'valid URL' => ['https://shlink.io', self::STATUS_OK, null];
        yield 'invalid URL' => ['htt:foo', self::STATUS_BAD_REQUEST, 'INVALID_URL'];
    }

    /**
     * @test
     * @dataProvider provideInvalidUrls
     */
    public function tryingToEditInvalidUrlReturnsNotFoundError(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $url = $this->buildShortUrlPath($shortCode, $domain);
        $resp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => []], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }

    /** @test */
    public function providingInvalidDataReturnsBadRequest(): void
    {
        $expectedDetail = 'Provided data is not valid';

        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => [
            'maxVisits' => 'not_a_number',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        self::assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        self::assertEquals('INVALID_ARGUMENT', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
    }

    /**
     * @test
     * @dataProvider provideDomains
     */
    public function metadataIsEditedOnProperShortUrlBasedOnDomain(?string $domain, string $expectedUrl): void
    {
        $shortCode = 'ghi789';
        $url = new Uri(sprintf('/short-urls/%s', $shortCode));

        if ($domain !== null) {
            $url = $url->withQuery(Query::build(['domain' => $domain]));
        }

        $editResp = $this->callApiWithKey(self::METHOD_PATCH, (string) $url, [RequestOptions::JSON => [
            'maxVisits' => 100,
        ]]);
        $editedShortUrl = $this->getJsonResponsePayload($this->callApiWithKey(self::METHOD_GET, (string) $url));

        self::assertEquals(self::STATUS_OK, $editResp->getStatusCode());
        self::assertEquals($domain, $editedShortUrl['domain']);
        self::assertEquals($expectedUrl, $editedShortUrl['longUrl']);
        self::assertEquals(100, $editedShortUrl['meta']['maxVisits'] ?? null);
    }

    public function provideDomains(): iterable
    {
        yield 'domain' => [
            'example.com',
            'https://blog.alejandrocelaya.com/2019/04/27/considerations-to-properly-use-open-source-software-projects/',
        ];
        yield 'no domain' => [null, 'https://shlink.io/documentation/'];
    }
}
