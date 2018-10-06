<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Exception\WrongIpException;
use function Shlinkio\Shlink\Common\json_decode;
use function sprintf;

class IpApiLocationResolver implements IpLocationResolverInterface
{
    private const SERVICE_PATTERN = 'http://ip-api.com/json/%s';

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
            return $this->mapFields(json_decode((string) $response->getBody()));
        } catch (GuzzleException $e) {
            throw WrongIpException::fromIpAddress($ipAddress, $e);
        } catch (InvalidArgumentException $e) {
            throw new WrongIpException('IP-API returned invalid body while locating IP address', 0, $e);
        }
    }

    private function mapFields(array $entry): array
    {
        return [
            'country_code' => $entry['countryCode'] ?? '',
            'country_name' => $entry['country'] ?? '',
            'region_name' => $entry['regionName'] ?? '',
            'city' => $entry['city'] ?? '',
            'latitude' => $entry['lat'] ?? '',
            'longitude' => $entry['lon'] ?? '',
            'time_zone' => $entry['timezone'] ?? '',
        ];
    }

    /**
     * Returns the interval in seconds that needs to be waited when the API limit is reached
     *
     * @return int
     */
    public function getApiInterval(): int
    {
        return 65; // ip-api interval is 1 minute. Return 5 extra seconds just in case
    }

    /**
     * Returns the limit of requests that can be performed to the API in a specific interval, or null if no limit exists
     *
     * @return int|null
     */
    public function getApiLimit(): ?int
    {
        return 145; // ip-api limit is 150 requests per minute. Leave 5 less requests just in case
    }
}
