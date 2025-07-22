<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;

use function sprintf;

class SetRedirectRulesTest extends ApiTestCase
{
    private const array LANGUAGE_EN_CONDITION = [
        'type' => 'language',
        'matchKey' => null,
        'matchValue' => 'en',
    ];

    #[Test]
    public function errorIsReturnedWhenInvalidUrlIsProvided(): void
    {
        $response = $this->callApiWithKey(self::METHOD_POST, '/short-urls/invalid/redirect-rules');
        $payload = $this->getJsonResponsePayload($response);

        self::assertEquals(404, $response->getStatusCode());
        self::assertEquals(404, $payload['status']);
        self::assertEquals('invalid', $payload['shortCode']);
        self::assertEquals('No URL found with short code "invalid"', $payload['detail']);
        self::assertEquals('Short URL not found', $payload['title']);
        self::assertEquals('https://shlink.io/api/error/short-url-not-found', $payload['type']);
    }

    #[Test]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'invalid',
            ],
        ],
    ]], 'invalid long URL')]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'https://example.com',
                'conditions' => 'foo',
            ],
        ],
    ]], 'non-array conditions')]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'https://example.com',
                'conditions' => [
                    [
                        'type' => 'invalid',
                        'matchKey' => null,
                        'matchValue' => 'foo',
                    ],
                ],
            ],
        ],
    ]], 'invalid condition type')]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'https://example.com',
                'conditions' => [
                    [
                        'type' => 'device',
                        'matchValue' => 'invalid-device',
                        'matchKey' => null,
                    ],
                ],
            ],
        ],
    ]], 'invalid device type')]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'https://example.com',
                'conditions' => [
                    [
                        'type' => 'ip-address',
                        'matchKey' => null,
                        'matchValue' => 'not an IP address',
                    ],
                ],
            ],
        ],
    ]], 'invalid IP address')]
    #[TestWith([[
        'redirectRules' => [
            [
                'longUrl' => 'https://example.com',
                'conditions' => [
                    [
                        'type' => 'geolocation-country-code',
                        'matchKey' => null,
                        'matchValue' => 'not a country code',
                    ],
                ],
            ],
        ],
    ]], 'invalid country code')]
    public function errorIsReturnedWhenInvalidDataIsProvided(array $bodyPayload): void
    {
        $response = $this->callApiWithKey(self::METHOD_POST, '/short-urls/abc123/redirect-rules', [
            RequestOptions::JSON => $bodyPayload,
        ]);
        $payload = $this->getJsonResponsePayload($response);

        self::assertEquals(400, $response->getStatusCode());
        self::assertEquals(400, $payload['status']);
        self::assertEquals('Provided data is not valid', $payload['detail']);
        self::assertEquals('Invalid data', $payload['title']);
        self::assertEquals('https://shlink.io/api/error/invalid-data', $payload['type']);
    }

    #[Test]
    #[TestWith(['def456', []])]
    #[TestWith(['abc123', [
        [
            'longUrl' => 'https://example.com/english-and-foo-query',
            'priority' => 1,
            'conditions' => [
                self::LANGUAGE_EN_CONDITION,
                [
                    'type' => 'any-value-query-param',
                    'matchKey' => 'foo',
                    'matchValue' => null,
                ],
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
                [
                    'type' => 'query-param',
                    'matchKey' => 'foo',
                    'matchValue' => 'bar',
                ],
            ],
        ],
    ]])]
    public function setsListOfRulesForShortUrl(string $shortCode, array $expectedRules): void
    {
        $response = $this->callApiWithKey(self::METHOD_POST, sprintf('/short-urls/%s/redirect-rules', $shortCode), [
            RequestOptions::JSON => [
                'redirectRules' => $expectedRules,
            ],
        ]);
        $payload = $this->getJsonResponsePayload($response);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($expectedRules, $payload['redirectRules']);
    }
}
