<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Geolocation;

use Locale;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use function abs;
use function class_exists;
use function is_numeric;
use function mb_substr;
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
    // Length of the string columns in which the location is persisted
    private const int MAX_LENGTH = 255;

    public function resolveFromRequest(ServerRequestInterface $request): Location|null
    {
        $countryCode = strtoupper(trim($request->getHeaderLine(self::COUNTRY_HEADER)));

        if ($countryCode === self::UNKNOWN_COUNTRY || preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            return null;
        }

        return new Location(
            countryCode: $countryCode,
            countryName: $this->resolveCountryName($countryCode),
            regionName: $this->stringHeader($request, self::REGION_HEADER),
            city: $this->stringHeader($request, self::CITY_HEADER),
            latitude: $this->coordinateHeader($request, self::LATITUDE_HEADER, max: 90),
            longitude: $this->coordinateHeader($request, self::LONGITUDE_HEADER, max: 180),
            timeZone: $this->stringHeader($request, self::TIMEZONE_HEADER),
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

    private function stringHeader(ServerRequestInterface $request, string $header): string
    {
        return mb_substr(trim($request->getHeaderLine($header)), 0, self::MAX_LENGTH);
    }

    /**
     * Values out of range are discarded. That includes INF, which is_numeric accepts for values like 1e999
     */
    private function coordinateHeader(ServerRequestInterface $request, string $header, int $max): float
    {
        $value = trim($request->getHeaderLine($header));
        if (!is_numeric($value)) {
            return 0.0;
        }

        $coordinate = (float) $value;
        return abs($coordinate) <= $max ? $coordinate : 0.0;
    }
}
