<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use ShlinkioTest\Shlink\Common\ApiTest\ApiTestCase;

class ListShortUrlsTest extends ApiTestCase
{
    /** @test */
    public function shortUrlsAreProperlyListed()
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
                        'originalUrl' => 'https://shlink.io',
                    ],
                ],
                'pagination' => [
                    'currentPage' => 1,
                    'pagesCount' => 1,
                    'itemsPerPage' => 10,
                    'itemsInCurrentPage' => 3,
                    'totalItems' => 3,
                ],
            ],
        ], $respPayload);
    }
}
