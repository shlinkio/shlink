<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\ApiTest;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Rest\Authentication\Plugin\ApiKeyHeaderPlugin;
use function Shlinkio\Shlink\Common\json_decode;
use function sprintf;

abstract class ApiTestCase extends TestCase implements StatusCodeInterface, RequestMethodInterface
{
    private const PATH_PREFX = '/rest/v1';

    /** @var ClientInterface */
    private static $client;

    public static function setApiClient(ClientInterface $client): void
    {
        self::$client = $client;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function callApi(string $method, string $uri, array $options = []): ResponseInterface
    {
        return self::$client->request($method, sprintf('%s%s', self::PATH_PREFX, $uri), $options);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function callApiWithKey(string $method, string $uri, array $options = []): ResponseInterface
    {
        $headers = $options['headers'] ?? [];
        $headers[ApiKeyHeaderPlugin::HEADER_NAME] = 'valid_api_key';
        $options['headers'] = $headers;

        return $this->callApi($method, $uri, $options);
    }

    protected function getJsonResponsePayload(ResponseInterface $resp): array
    {
        return json_decode((string) $resp->getBody());
    }
}
