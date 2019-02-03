<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\ApiTest;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use function Shlinkio\Shlink\Common\json_decode;
use function sprintf;

abstract class ApiTestCase extends TestCase implements StatusCodeInterface, RequestMethodInterface
{
    private const REST_PATH_PREFIX = '/rest/v1';

    /** @var ClientInterface */
    private static $client;
    /** @var callable */
    private static $seedFixtures;

    public static function setApiClient(ClientInterface $client): void
    {
        self::$client = $client;
    }

    public static function setSeedFixturesCallback(callable $seedFixtures): void
    {
        self::$seedFixtures = $seedFixtures;
    }

    public function setUp(): void
    {
        if (self::$seedFixtures) {
            (self::$seedFixtures)();
        }
    }

    protected function callApi(string $method, string $uri, array $options = []): ResponseInterface
    {
        return self::$client->request($method, sprintf('%s%s', self::REST_PATH_PREFIX, $uri), $options);
    }

    protected function callApiWithKey(string $method, string $uri, array $options = []): ResponseInterface
    {
        $headers = $options[RequestOptions::HEADERS] ?? [];
        $headers[ApiKeyHeaderPlugin::HEADER_NAME] = 'valid_api_key';
        $options[RequestOptions::HEADERS] = $headers;

        return $this->callApi($method, $uri, $options);
    }

    protected function getJsonResponsePayload(ResponseInterface $resp): array
    {
        return json_decode((string) $resp->getBody());
    }

    protected function callShortUrl(string $shortCode): ResponseInterface
    {
        return self::$client->request(self::METHOD_GET, sprintf('/%s', $shortCode), [
            RequestOptions::ALLOW_REDIRECTS => false,
        ]);
    }
}
