<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ListShortUrlsTest extends ApiTestCase
{
    /** @test */
    public function shortUrlsAreProperlyListed(): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/short-urls');
        $respPayload = $this->getJsonResponsePayload($resp);

        $this->assertEquals(self::STATUS_OK, $resp->getStatusCode());
        $this->assertEquals([
            'shortUrls' => [
                'data' => [
                    [
                        'shortCode' => 'abc123',
                        'shortUrl' => 'http://doma.in/abc123',
                        'longUrl' => 'https://shlink.io',
                        'dateCreated' => '2019-01-01T00:00:00+00:00',
                        'visitsCount' => 3,
                        'tags' => ['foo'],
                        'meta' => [
                            'validSince' => null,
                            'validUntil' => null,
                            'maxVisits' => null,
                        ],
                        'originalUrl' => 'https://shlink.io',
                    ],
                    [
                        'shortCode' => 'def456',
                        'shortUrl' => 'http://doma.in/def456',
                        'longUrl' =>
                            'https://blog.alejandrocelaya.com/2017/12/09'
                            . '/acmailer-7-0-the-most-important-release-in-a-long-time/',
                        'dateCreated' => '2019-01-01T00:00:00+00:00',
                        'visitsCount' => 2,
                        'tags' => ['foo', 'bar'],
                        'meta' => [
                            'validSince' => '2020-05-01T00:00:00+00:00',
                            'validUntil' => null,
                            'maxVisits' => null,
                        ],
                        'originalUrl' =>
                            'https://blog.alejandrocelaya.com/2017/12/09'
                            . '/acmailer-7-0-the-most-important-release-in-a-long-time/',
                    ],
                    [
                        'shortCode' => 'custom',
                        'shortUrl' => 'http://doma.in/custom',
                        'longUrl' => 'https://shlink.io',
                        'dateCreated' => '2019-01-01T00:00:00+00:00',
                        'visitsCount' => 0,
                        'tags' => [],
                        'meta' => [
                            'validSince' => null,
                            'validUntil' => null,
                            'maxVisits' => 2,
                        ],
                        'originalUrl' => 'https://shlink.io',
                    ],
                    [
                        'shortCode' => 'ghi789',
                        'shortUrl' => 'http://example.com/ghi789',
                        'longUrl' =>
                            'https://blog.alejandrocelaya.com/2019/04/27'
                            . '/considerations-to-properly-use-open-source-software-projects/',
                        'dateCreated' => '2019-01-01T00:00:00+00:00',
                        'visitsCount' => 0,
                        'tags' => [],
                        'meta' => [
                            'validSince' => null,
                            'validUntil' => null,
                            'maxVisits' => null,
                        ],
                        'originalUrl' =>
                            'https://blog.alejandrocelaya.com/2019/04/27'
                            . '/considerations-to-properly-use-open-source-software-projects/',
                    ],
                    [
                        'shortCode' => 'custom-with-domain',
                        'shortUrl' => 'http://some-domain.com/custom-with-domain',
                        'longUrl' => 'https://google.com',
                        'dateCreated' => '2019-01-01T00:00:00+00:00',
                        'visitsCount' => 0,
                        'tags' => [],
                        'meta' => [
                            'validSince' => null,
                            'validUntil' => null,
                            'maxVisits' => null,
                        ],
                        'originalUrl' => 'https://google.com',
                    ],
                ],
                'pagination' => [
                    'currentPage' => 1,
                    'pagesCount' => 1,
                    'itemsPerPage' => 10,
                    'itemsInCurrentPage' => 5,
                    'totalItems' => 5,
                ],
            ],
        ], $respPayload);
    }
}
