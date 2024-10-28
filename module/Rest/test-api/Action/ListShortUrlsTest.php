<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function count;

class ListShortUrlsTest extends ApiTestCase
{
    private const SHORT_URL_SHLINK_WITH_TITLE = [
        'shortCode' => 'abc123',
        'shortUrl' => 'http://s.test/abc123',
        'longUrl' => 'https://shlink.io',
        'dateCreated' => '2018-05-01T00:00:00+00:00',
        'visitsSummary' => [
            'total' => 3,
            'nonBots' => 3,
            'bots' => 0,
        ],
        'tags' => ['foo'],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => 2,
        ],
        'domain' => null,
        'title' => 'My cool title',
        'crawlable' => true,
        'forwardQuery' => true,
        'hasRedirectRules' => false,
    ];
    private const SHORT_URL_DOCS = [
        'shortCode' => 'ghi789',
        'shortUrl' => 'http://s.test/ghi789',
        'longUrl' => 'https://shlink.io/documentation/',
        'dateCreated' => '2018-05-01T00:00:00+00:00',
        'visitsSummary' => [
            'total' => 2,
            'nonBots' => 2,
            'bots' => 0,
        ],
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => '2020-05-01T00:00:00+00:00',
            'maxVisits' => null,
        ],
        'domain' => null,
        'title' => null,
        'crawlable' => false,
        'forwardQuery' => true,
        'hasRedirectRules' => false,
    ];
    private const SHORT_URL_CUSTOM_SLUG_AND_DOMAIN = [
        'shortCode' => 'custom-with-domain',
        'shortUrl' => 'http://some-domain.com/custom-with-domain',
        'longUrl' => 'https://google.com',
        'dateCreated' => '2018-10-20T00:00:00+00:00',
        'visitsSummary' => [
            'total' => 0,
            'nonBots' => 0,
            'bots' => 0,
        ],
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ],
        'domain' => 'some-domain.com',
        'title' => null,
        'crawlable' => false,
        'forwardQuery' => true,
        'hasRedirectRules' => false,
    ];
    private const SHORT_URL_META = [
        'shortCode' => 'def456',
        'shortUrl' => 'http://s.test/def456',
        'longUrl' =>
            'https://blog.alejandrocelaya.com/2017/12/09'
            . '/acmailer-7-0-the-most-important-release-in-a-long-time/',
        'dateCreated' => '2019-01-01T00:00:10+00:00',
        'visitsSummary' => [
            'total' => 2,
            'nonBots' => 1,
            'bots' => 1,
        ],
        'tags' => ['bar', 'foo'],
        'meta' => [
            'validSince' => '2020-05-01T00:00:00+00:00',
            'validUntil' => null,
            'maxVisits' => null,
        ],
        'domain' => null,
        'title' => null,
        'crawlable' => false,
        'forwardQuery' => true,
        'hasRedirectRules' => true,
    ];
    private const SHORT_URL_CUSTOM_SLUG = [
        'shortCode' => 'custom',
        'shortUrl' => 'http://s.test/custom',
        'longUrl' => 'https://shlink.io',
        'dateCreated' => '2019-01-01T00:00:20+00:00',
        'visitsSummary' => [
            'total' => 0,
            'nonBots' => 0,
            'bots' => 0,
        ],
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => 2,
        ],
        'domain' => null,
        'title' => null,
        'crawlable' => true,
        'forwardQuery' => false,
        'hasRedirectRules' => false,
    ];
    private const SHORT_URL_CUSTOM_DOMAIN = [
        'shortCode' => 'ghi789',
        'shortUrl' => 'http://example.com/ghi789',
        'longUrl' =>
            'https://blog.alejandrocelaya.com/2019/04/27'
            . '/considerations-to-properly-use-open-source-software-projects/',
        'dateCreated' => '2019-01-01T00:00:30+00:00',
        'visitsSummary' => [
            'total' => 0,
            'nonBots' => 0,
            'bots' => 0,
        ],
        'tags' => ['foo'],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ],
        'domain' => 'example.com',
        'title' => null,
        'crawlable' => false,
        'forwardQuery' => true,
        'hasRedirectRules' => false,
    ];

    #[Test, DataProvider('provideFilteredLists')]
    public function shortUrlsAreProperlyListed(array $query, array $expectedShortUrls, string $apiKey): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls', [RequestOptions::QUERY => $query], $apiKey);
        $respPayload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertEquals([
            'shortUrls' => [
                'data' => $expectedShortUrls,
                'pagination' => $this->buildPagination(count($expectedShortUrls)),
            ],
        ], $respPayload);
    }

    public static function provideFilteredLists(): iterable
    {
        yield [[], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_SHLINK_WITH_TITLE,
            self::SHORT_URL_DOCS,
        ], 'valid_api_key'];
        yield [['excludePastValidUntil' => 'true'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['excludeMaxVisitsReached' => 'true'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_DOCS,
        ], 'valid_api_key'];
        yield [['orderBy' => 'shortCode'], [
            self::SHORT_URL_SHLINK_WITH_TITLE,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_DOCS,
            self::SHORT_URL_CUSTOM_DOMAIN,
        ], 'valid_api_key'];
        yield [['orderBy' => 'shortCode-DESC'], [
            self::SHORT_URL_DOCS,
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['orderBy' => 'title-DESC'], [
            self::SHORT_URL_SHLINK_WITH_TITLE,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_DOCS,
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
        ], 'valid_api_key'];
        yield [['startDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
        ], 'valid_api_key'];
        yield [['endDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_SHLINK_WITH_TITLE,
            self::SHORT_URL_DOCS,
        ], 'valid_api_key'];
        yield [['tags' => ['foo']], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['tags' => ['bar']], [
            self::SHORT_URL_META,
        ], 'valid_api_key'];
        yield [['tags' => ['foo', 'bar']], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['tags' => ['foo', 'bar'], 'tagsMode' => 'any'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['tags' => ['foo', 'bar'], 'tagsMode' => 'all'], [
            self::SHORT_URL_META,
        ], 'valid_api_key'];
        yield [['tags' => ['foo', 'bar', 'baz']], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['tags' => ['foo', 'bar', 'baz'], 'tagsMode' => 'all'], [], 'valid_api_key'];
        yield [['tags' => ['foo'], 'endDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['searchTerm' => 'alejandro'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
            self::SHORT_URL_META,
        ], 'valid_api_key'];
        yield [['searchTerm' => 'cool'], [
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'valid_api_key'];
        yield [['searchTerm' => 'example.com'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
        ], 'valid_api_key'];
        yield [['domain' => 'example.com'], [
            self::SHORT_URL_CUSTOM_DOMAIN,
        ], 'valid_api_key'];
        yield [['domain' => Domain::DEFAULT_AUTHORITY], [
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
            self::SHORT_URL_DOCS,
        ], 'valid_api_key'];
        yield [[], [
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_META,
            self::SHORT_URL_SHLINK_WITH_TITLE,
        ], 'author_api_key'];
        yield [[], [
            self::SHORT_URL_CUSTOM_DOMAIN,
        ], 'domain_api_key'];
    }

    private function buildPagination(int $itemsCount): array
    {
        return [
            'currentPage' => 1,
            'pagesCount' => 1,
            'itemsPerPage' => 10,
            'itemsInCurrentPage' => $itemsCount,
            'totalItems' => $itemsCount,
        ];
    }

    #[Test, DataProvider('provideInvalidFiltering')]
    public function errorIsReturnedWhenProvidingInvalidValues(array $query, array $expectedInvalidElements): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls', [RequestOptions::QUERY => $query]);
        $respPayload = $this->getJsonResponsePayload($resp);

        self::assertEquals(400, $resp->getStatusCode());
        self::assertEquals([
            'invalidElements' => $expectedInvalidElements,
            'title' => 'Invalid data',
            'type' => 'https://shlink.io/api/error/invalid-data',
            'status' => 400,
            'detail' => 'Provided data is not valid',
        ], $respPayload);
    }

    public static function provideInvalidFiltering(): iterable
    {
        yield [['tagsMode' => 'invalid'], ['tagsMode']];
        yield [['orderBy' => 'invalid'], ['orderBy']];
        yield [['orderBy' => 'invalid', 'tagsMode' => 'invalid'], ['tagsMode', 'orderBy']];
    }
}
