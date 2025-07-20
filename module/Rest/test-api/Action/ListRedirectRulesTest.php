<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class ListRedirectRulesTest extends ApiTestCase
{
    private const array LANGUAGE_EN_CONDITION = [
        'type' => 'language',
        'matchKey' => null,
        'matchValue' => 'en',
    ];
    private const array QUERY_FOO_BAR_CONDITION = [
        'type' => 'query-param',
        'matchKey' => 'foo',
        'matchValue' => 'bar',
    ];

    #[Test]
    public function errorIsReturnedWhenInvalidUrlIsFetched(): void
    {
        $response = $this->callApiWithKey(self::METHOD_GET, '/short-urls/invalid/redirect-rules');
        $payload = $this->getJsonResponsePayload($response);

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals(404, $payload['status']);
        self::assertEquals('invalid', $payload['shortCode']);
        self::assertEquals('No URL found with short code "invalid"', $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
    }

    #[Test]
    #[TestWith(['abc123', []])]
    #[TestWith(['def456', [
        [
            'longUrl' => 'https://example.com/english-and-foo-query',
            'priority' => 1,
            'conditions' => [
                self::LANGUAGE_EN_CONDITION,
                self::QUERY_FOO_BAR_CONDITION,
            ],
        ],
        [
            'longUrl' => 'https://example.com/multiple-query-params',
            'priority' => 2,
            'conditions' => [
                [
                    'type' => 'query-param',
                    'matchKey' => 'hello',
                    'matchValue' => 'world',
                ],
                self::QUERY_FOO_BAR_CONDITION,
            ],
        ],
        [
            'longUrl' => 'https://example.com/only-english',
            'priority' => 3,
            'conditions' => [self::LANGUAGE_EN_CONDITION],
        ],
        [
            'longUrl' => 'android://foo/bar',
            'priority' => 4,
            'conditions' => [
                [
                    'type' => 'device',
                    'matchKey' => null,
                    'matchValue' => 'android',
                ],
            ],
        ],
        [
            'longUrl' => 'fb://profile/33138223345',
            'priority' => 5,
            'conditions' => [
                [
                    'type' => 'device',
                    'matchKey' => null,
                    'matchValue' => 'ios',
                ],
            ],
        ],
        [
            'longUrl' => 'https://example.com/static-ip-address',
            'priority' => 6,
            'conditions' => [
                [
                    'type' => 'ip-address',
                    'matchKey' => null,
                    'matchValue' => '1.2.3.4',
                ],
            ],
        ],
        [
            'longUrl' => 'https://example.com/linux',
            'priority' => 7,
            'conditions' => [
                [
                    'type' => 'device',
                    'matchKey' => null,
                    'matchValue' => 'linux',
                ],
            ],
        ],
    ]])]
    public function returnsListOfRulesForShortUrl(string $shortCode, array $expectedRules): void
    {
        $response = $this->callApiWithKey(self::METHOD_GET, sprintf('/short-urls/%s/redirect-rules', $shortCode));
        $payload = $this->getJsonResponsePayload($response);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedRules, $payload['redirectRules']);
    }
}
