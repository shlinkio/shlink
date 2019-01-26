<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common\ApiTest;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class ApiTestCase extends TestCase implements StatusCodeInterface, RequestMethodInterface
{
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
        return self::$client->request($method, $uri, $options);
    }
}
