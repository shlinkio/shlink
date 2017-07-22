<?php
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
     * @param $ipAddress
     * @return array
     */
    public function resolveIpLocation($ipAddress)
    {
        try {
            $response = $this->httpClient->get(sprintf(self::SERVICE_PATTERN, $ipAddress));
            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            throw WrongIpException::fromIpAddress($ipAddress, $e);
        }
    }
}
