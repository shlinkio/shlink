<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\NotFoundUrlHelpersTrait;

use function sprintf;

class ResolveShortUrlTest extends ApiTestCase
{
    use NotFoundUrlHelpersTrait;

    #[Test, DataProvider('provideDisabledMeta')]
    public function shortUrlIsProperlyResolvedEvenWhenNotEnabled(array $disabledMeta): void
    {
        $shortCode = 'abc123';
        $url = sprintf('/short-urls/%s', $shortCode);
        $this->callShortUrl($shortCode);

        $editResp = $this->callApiWithKey(self::METHOD_PATCH, $url, [RequestOptions::JSON => $disabledMeta]);
        $visitResp = $this->callShortUrl($shortCode);
        $fetchResp = $this->callApiWithKey(self::METHOD_GET, $url);

        self::assertEquals(self::STATUS_OK, $editResp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $visitResp->getStatusCode());
        self::assertEquals(self::STATUS_OK, $fetchResp->getStatusCode());
    }

    public static function provideDisabledMeta(): iterable
    {
        $now = Chronos::now();

        yield 'future validSince' => [['validSince' => $now->addMonth()->toAtomString()]];
        yield 'past validUntil' => [['validUntil' => $now->subMonth()->toAtomString()]];
        yield 'maxVisits reached' => [['maxVisits' => 1]];
    }

    #[Test, DataProvider('provideInvalidUrls')]
    public function tryingToResolveInvalidUrlReturnsNotFoundError(
        string $shortCode,
        ?string $domain,
        string $expectedDetail,
        string $apiKey,
    ): void {
        $resp = $this->callApiWithKey(self::METHOD_GET, $this->buildShortUrlPath($shortCode, $domain), [], $apiKey);
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_NOT_FOUND, $resp->getStatusCode());
        self::assertEquals(self::STATUS_NOT_FOUND, $payload['status']);
        self::assertEquals('INVALID_SHORTCODE', $payload['type']);
        self::assertEquals($expectedDetail, $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals($shortCode, $payload['shortCode']);
        self::assertEquals($domain, $payload['domain'] ?? null);
    }
}
