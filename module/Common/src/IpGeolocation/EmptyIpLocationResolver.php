<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\IpGeolocation;

use Shlinkio\Shlink\Common\Exception\WrongIpException;

class EmptyIpLocationResolver implements IpLocationResolverInterface
{
    /**
     * @throws WrongIpException
     */
    public function resolveIpLocation(string $ipAddress): array
    {
        return [
            'country_code' => '',
            'country_name' => '',
            'region_name' => '',
            'city' => '',
            'latitude' => '',
            'longitude' => '',
            'time_zone' => '',
        ];
    }
}
