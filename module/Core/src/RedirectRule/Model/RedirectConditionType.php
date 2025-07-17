<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Model;

use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;
use function Shlinkio\Shlink\Core\enumValues;

use const Shlinkio\Shlink\ISO_COUNTRY_CODES;

enum RedirectConditionType: string
{
    case DEVICE = 'device';
    case LANGUAGE = 'language';
    case QUERY_PARAM = 'query-param';
    case ANY_VALUE_QUERY_PARAM = 'any-value-query-param';
    case VALUELESS_QUERY_PARAM = 'valueless-query-param';
    case IP_ADDRESS = 'ip-address';
    case GEOLOCATION_COUNTRY_CODE = 'geolocation-country-code';
    case GEOLOCATION_CITY_NAME = 'geolocation-city-name';

    /**
     * Tells if a value is valid for the condition type
     */
    public function isValid(string $value): bool
    {
        return match ($this) {
            RedirectConditionType::DEVICE => contains($value, enumValues(DeviceType::class)),
            // RedirectConditionType::LANGUAGE => TODO Validate at least format,
            RedirectConditionType::IP_ADDRESS => IpAddressUtils::isStaticIpCidrOrWildcard($value),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => contains($value, ISO_COUNTRY_CODES),
            RedirectConditionType::QUERY_PARAM,
            RedirectConditionType::ANY_VALUE_QUERY_PARAM,
            RedirectConditionType::VALUELESS_QUERY_PARAM => $value !== '',
            // FIXME We should at least validate the value is not empty
            //  default => $value !== '',
            default => true,
        };
    }
}
