<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Common\Exception\WrongIpException;

use function Shlinkio\Shlink\Common\json_decode;
use function sprintf;

class IpApiLocationResolver implements IpLocationResolverInterface
{
    private const SERVICE_PATTERN = 'http://ip-api.com/json/%s';

    /** @var Client */
    private $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): Model\Location
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

    private function mapFields(array $entry): Model\Location
    {
        return new Model\Location(
            (string) ($entry['countryCode'] ?? ''),
            (string) ($entry['country'] ?? ''),
            (string) ($entry['regionName'] ?? ''),
            (string) ($entry['city'] ?? ''),
            (float) ($entry['lat'] ?? 0.0),
            (float) ($entry['lon'] ?? 0.0),
            (string) ($entry['timezone'] ?? '')
        );
    }
}
