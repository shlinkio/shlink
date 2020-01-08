<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Cake\Chronos\Chronos;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function count;

class ListShortUrlsTest extends ApiTestCase
{
    private const SHORT_URL_SHLINK = [
        'shortCode' => 'abc123',
        'shortUrl' => 'http://doma.in/abc123',
        'longUrl' => 'https://shlink.io',
        'dateCreated' => '2018-05-01T00:00:00+00:00',
        'visitsCount' => 3,
        'tags' => ['foo'],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ],
    ];
    private const SHORT_URL_CUSTOM_SLUG_AND_DOMAIN = [
        'shortCode' => 'custom-with-domain',
        'shortUrl' => 'http://some-domain.com/custom-with-domain',
        'longUrl' => 'https://google.com',
        'dateCreated' => '2018-10-20T00:00:00+00:00',
        'visitsCount' => 0,
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ],
    ];
    private const SHORT_URL_META = [
        'shortCode' => 'def456',
        'shortUrl' => 'http://doma.in/def456',
        'longUrl' =>
            'https://blog.alejandrocelaya.com/2017/12/09'
            . '/acmailer-7-0-the-most-important-release-in-a-long-time/',
        'dateCreated' => '2019-01-01T00:00:10+00:00',
        'visitsCount' => 2,
        'tags' => ['bar', 'foo'],
        'meta' => [
            'validSince' => '2020-05-01T00:00:00+00:00',
            'validUntil' => null,
            'maxVisits' => null,
        ],
    ];
    private const SHORT_URL_CUSTOM_SLUG = [
        'shortCode' => 'custom',
        'shortUrl' => 'http://doma.in/custom',
        'longUrl' => 'https://shlink.io',
        'dateCreated' => '2019-01-01T00:00:20+00:00',
        'visitsCount' => 0,
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => 2,
        ],
    ];
    private const SHORT_URL_CUSTOM_DOMAIN = [
        'shortCode' => 'ghi789',
        'shortUrl' => 'http://example.com/ghi789',
        'longUrl' =>
            'https://blog.alejandrocelaya.com/2019/04/27'
            . '/considerations-to-properly-use-open-source-software-projects/',
        'dateCreated' => '2019-01-01T00:00:30+00:00',
        'visitsCount' => 0,
        'tags' => [],
        'meta' => [
            'validSince' => null,
            'validUntil' => null,
            'maxVisits' => null,
        ],
    ];

    /**
     * @test
     * @dataProvider provideFilteredLists
     */
    public function shortUrlsAreProperlyListed(array $query, array $expectedShortUrls): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls', [RequestOptions::QUERY => $query]);
        $respPayload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_OK, $resp->getStatusCode());
        $this->assertEquals([
            'shortUrls' => [
                'data' => $expectedShortUrls,
                'pagination' => $this->buildPagination(count($expectedShortUrls)),
            ],
        ], $respPayload);
    }

    public function provideFilteredLists(): iterable
    {
        yield [[], [
            self::SHORT_URL_SHLINK,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_CUSTOM_DOMAIN,
        ]];
        yield [['orderBy' => 'shortCode'], [
            self::SHORT_URL_SHLINK,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_DOMAIN,
        ]];
        yield [['startDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_SLUG,
            self::SHORT_URL_CUSTOM_DOMAIN,
        ]];
        yield [['endDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_SHLINK,
            self::SHORT_URL_CUSTOM_SLUG_AND_DOMAIN,
        ]];
        yield [['tags' => ['foo']], [
            self::SHORT_URL_SHLINK,
            self::SHORT_URL_META,
        ]];
        yield [['tags' => ['bar']], [
            self::SHORT_URL_META,
        ]];
        yield [['tags' => ['foo'], 'endDate' => Chronos::parse('2018-12-01')->toAtomString()], [
            self::SHORT_URL_SHLINK,
        ]];
        yield [['searchTerm' => 'alejandro'], [
            self::SHORT_URL_META,
            self::SHORT_URL_CUSTOM_DOMAIN,
        ]];
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
}
