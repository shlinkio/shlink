<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Action;

use PHPUnit\Framework\Attributes\Test;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\TestUtils\ApiTest\ApiTestCase;
use ShlinkioApiTest\Shlink\Rest\Utils\WithEnvVars;

class MercureInfoTest extends ApiTestCase
{
    #[Test]
    public function mercureServerInfoIsReturnedIfConfigured(): void
    {
        $resp = $this->callApiWithKey('GET', '/mercure-info');
        self::assertEquals(501, $resp->getStatusCode());
    }

    #[Test, WithEnvVars([
        EnvVars::MERCURE_ENABLED->value => true,
        EnvVars::MERCURE_PUBLIC_HUB_URL->value => 'https://mercure.example.com',
        EnvVars::MERCURE_JWT_SECRET->value => 'mercure_jwt_key_long_enough_to_avoid_error',
    ])]
    public function errorIsReturnedIfMercureServerIsNotConfigured(): void
    {
        $resp = $this->callApiWithKey('GET', '/mercure-info');
        $payload = $this->getJsonResponsePayload($resp);

        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('https://mercure.example.com/.well-known/mercure', $payload['mercureHubUrl']);
    }
}
