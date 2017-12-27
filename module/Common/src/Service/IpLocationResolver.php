<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Shlinkio\Shlink\Common\Exception\WrongIpException;

class IpLocationResolver implements IpLocationResolverInterface
{
    const SERVICE_PATTERN = 'http://freegeoip.net/json/%s';

    /**
     * @var Client
     */
    private $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $ipAddress
     * @return array
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array
    {
        try {
            $response = $this->httpClient->get(sprintf(self::SERVICE_PATTERN, $ipAddress));
            return json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            /** @var \Throwable $e */
            throw WrongIpException::fromIpAddress($ipAddress, $e);
        }
    }
}
