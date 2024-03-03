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
    private const LANGUAGE_EN_CONDITION = [
        'type' => 'language',
        'matchKey' => null,
        'matchValue' => 'en',
    ];
    private const QUERY_FOO_BAR_CONDITION = [
        'type' => 'query-param',
        'matchKey' => 'foo',
        'matchValue' => 'bar',
    ];

    #[Test]
    public function errorIsReturnedWhenInvalidUrlProvided(): void
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
    public function errorIsReturnedWhenInvalidDataProvided(): void
    {
        $response = $this->callApiWithKey(self::METHOD_POST, '/short-urls/abc123/redirect-rules', [
            RequestOptions::JSON => [
                'redirectRules' => [
                    [
                        'longUrl' => 'invalid',
                    ],
                ],
            ],
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
