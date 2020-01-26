<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function Functional\first;
use function sprintf;

class EditShortUrlActionTest extends ApiTestCase
{
    use ArraySubsetAsserts;

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

        $this->assertEquals(self::STATUS_NO_CONTENT, $editWithProvidedMeta->getStatusCode());
        $this->assertEquals(self::STATUS_NO_CONTENT, $editWithResetMeta->getStatusCode());
        $this->assertEquals($resetMeta, $metaAfterResetting);
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
        // FIXME Call GET /short-urls/{shortCode} once issue https://github.com/shlinkio/shlink/issues/628 is fixed
        $allShortUrls = $this->getJsonResponsePayload($this->callApiWithKey(self::METHOD_GET, '/short-urls'));
        $list = $allShortUrls['shortUrls']['data'] ?? [];
        $matchingShortUrl = first($list, fn (array $shortUrl) => $shortUrl['shortCode'] ?? '' === $shortCode);

        return $matchingShortUrl['meta'] ?? null;
    }

    /** @test */
    public function tryingToEditInvalidUrlReturnsNotFoundError(): void
    {
        $expectedDetail = 'No URL found with short code "invalid"';

        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => []]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        $this->assertEquals('INVALID_SHORTCODE', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Short URL not found', $payload['title']);
        $this->assertEquals('invalid', $payload['shortCode']);
    }

    /** @test */
    public function providingInvalidDataReturnsBadRequest(): void
    {
        $expectedDetail = 'Provided data is not valid';

        $resp = $this->callApiWithKey(self::METHOD_PATCH, '/short-urls/invalid', [RequestOptions::JSON => [
            'maxVisits' => 'not_a_number',
        ]]);
        $payload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_BAD_REQUEST, $resp->getStatusCode());
        $this->assertEquals(self::STATUS_BAD_REQUEST, $payload['status']);
        $this->assertEquals('INVALID_ARGUMENT', $payload['type']);
        $this->assertEquals($expectedDetail, $payload['detail']);
        $this->assertEquals('Invalid data', $payload['title']);
    }
}
