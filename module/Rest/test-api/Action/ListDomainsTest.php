<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

class ListDomainsTest extends ApiTestCase
{
    /**
     * @test
     * @dataProvider provideApiKeysAndDomains
     */
    public function domainsAreProperlyListed(string $apiKey, array $expectedDomains): void
    {
        $resp = $this->callApiWithKey(self::METHOD_GET, '/domains', [], $apiKey);
        $respPayload = $this->getJsonResponsePayload($resp);

        self::assertEquals(self::STATUS_OK, $resp->getStatusCode());
        self::assertEquals([
            'domains' => [
                'data' => $expectedDomains,
                'defaultRedirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
        ], $respPayload);
    }

    public function provideApiKeysAndDomains(): iterable
    {
        yield 'admin API key' => ['valid_api_key', [
            [
                'domain' => 'doma.in',
                'isDefault' => true,
                'redirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
            [
                'domain' => 'detached-with-redirects.com',
                'isDefault' => false,
                'redirects' => [
                    'baseUrlRedirect' => 'foo.com',
                    'regular404Redirect' => 'bar.com',
                    'invalidShortUrlRedirect' => null,
                ],
            ],
            [
                'domain' => 'example.com',
                'isDefault' => false,
                'redirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
            [
                'domain' => 'some-domain.com',
                'isDefault' => false,
                'redirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
        ]];
        yield 'author API key' => ['author_api_key', [
            [
                'domain' => 'doma.in',
                'isDefault' => true,
                'redirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
        ]];
        yield 'domain API key' => ['domain_api_key', [
            [
                'domain' => 'example.com',
                'isDefault' => false,
                'redirects' => [
                    'baseUrlRedirect' => null,
                    'regular404Redirect' => null,
                    'invalidShortUrlRedirect' => null,
                ],
            ],
        ]];
    }
}
