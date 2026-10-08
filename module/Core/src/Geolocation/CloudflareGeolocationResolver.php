<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

use Locale;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function class_exists;
use function is_numeric;
use function preg_match;
use function strtoupper;
use function trim;

final readonly class CloudflareGeolocationResolver
{
    public const string CITY_HEADER = 'CF-IPCity';
    public const string COUNTRY_HEADER = 'CF-IPCountry';
    public const string LATITUDE_HEADER = 'CF-IPLatitude';
    public const string LONGITUDE_HEADER = 'CF-IPLongitude';
    public const string REGION_HEADER = 'CF-Region';
    public const string TIMEZONE_HEADER = 'CF-Timezone';

    // Value Cloudflare sets in the country header when the country is unknown. Tor is reported as T1, which is not a
    // valid country code either
    private const string UNKNOWN_COUNTRY = 'XX';

    public function resolveFromRequest(ServerRequestInterface $request): Location|null
    {
        $countryCode = strtoupper(trim($request->getHeaderLine(self::COUNTRY_HEADER)));

        if ($countryCode === self::UNKNOWN_COUNTRY || preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            return null;
        }

        return new Location(
            countryCode: $countryCode,
            countryName: $this->resolveCountryName($countryCode),
            regionName: trim($request->getHeaderLine(self::REGION_HEADER)),
            city: trim($request->getHeaderLine(self::CITY_HEADER)),
            latitude: $this->floatHeader($request, self::LATITUDE_HEADER),
            longitude: $this->floatHeader($request, self::LONGITUDE_HEADER),
            timeZone: trim($request->getHeaderLine(self::TIMEZONE_HEADER)),
        );
    }

    private function resolveCountryName(string $countryCode): string
    {
        if (!class_exists(Locale::class)) {
            return '';
        }

        $countryName = Locale::getDisplayRegion('-' . $countryCode, 'en');

        return $countryName === false || $countryName === $countryCode ? '' : $countryName;
    }

    private function floatHeader(ServerRequestInterface $request, string $header): float
    {
        $value = trim($request->getHeaderLine($header));

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
