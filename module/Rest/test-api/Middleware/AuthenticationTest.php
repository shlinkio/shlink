<?php
declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Middleware;

use GuzzleHttp\Exception\ClientException;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPlugin;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use ShlinkioTest\Shlink\Common\ApiTest\ApiTestCase;
use function implode;
use function Shlinkio\Shlink\Common\json_decode;
use function sprintf;

class AuthenticationTest extends ApiTestCase
{
    /**
     * @test
     */
    public function authorizationErrorIsReturnedIfNoApiKeyIsSent()
    {
        try {
            $this->callApi(self::METHOD_GET, '/short-codes');
        } catch (ClientException $e) {
            ['error' => $error, 'message' => $message] = $this->getJsonResponsePayload($e->getResponse());

            $this->assertEquals(self::STATUS_UNAUTHORIZED, $e->getCode());
            $this->assertEquals(RestUtils::INVALID_AUTHORIZATION_ERROR, $error);
            $this->assertEquals(
                sprintf(
                    'Expected one of the following authentication headers, but none were provided, ["%s"]',
                    implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
                ),
                $message
            );
        }
    }

    /**
     * @test
     * @dataProvider provideInvalidApiKeys
     */
    public function apiKeyErrorIsReturnedWhenProvidedApiKeyIsInvalid(string $apiKey)
    {
        try {
            $this->callApi(self::METHOD_GET, '/short-codes', [
                'headers' => [
                    ApiKeyHeaderPlugin::HEADER_NAME => $apiKey,
                ],
            ]);
        } catch (ClientException $e) {
            ['error' => $error, 'message' => $message] = json_decode((string) $e->getResponse()->getBody());

            $this->assertEquals(self::STATUS_UNAUTHORIZED, $e->getCode());
            $this->assertEquals(RestUtils::INVALID_API_KEY_ERROR, $error);
            $this->assertEquals('Provided API key does not exist or is invalid.', $message);
        }
    }

    public function provideInvalidApiKeys(): array
    {
        return [
            'key which does not exist' => ['invalid'],
            'key which is expired' => ['expired_api_key'],
            'key which is disabled' => ['disabled_api_key'],
        ];
    }
}
