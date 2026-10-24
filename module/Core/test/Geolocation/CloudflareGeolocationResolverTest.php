<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Geolocation;

use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Geolocation\CloudflareGeolocationResolver;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

class CloudflareGeolocationResolverTest extends TestCase
{
    private CloudflareGeolocationResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CloudflareGeolocationResolver();
    }

    #[Test]
    #[TestWith([null], 'no header')]
    #[TestWith([''], 'empty header')]
    #[TestWith(['XX'], 'unknown country')]
    #[TestWith(['T1'], 'tor network')]
    #[TestWith(['USA'], 'too long')]
    #[TestWith(['1A'], 'invalid characters')]
    public function nullIsReturnedWhenCountryHeaderIsNotUsable(string|null $countryHeader): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($countryHeader !== null) {
            $request = $request->withHeader(CloudflareGeolocationResolver::COUNTRY_HEADER, $countryHeader);
        }

        self::assertNull($this->resolver->resolveFromRequest($request));
    }

    /**
     * @param array<string, string> $headers
     */
    #[Test, DataProvider('provideHeaders')]
    public function locationIsResolvedFromHeaders(array $headers, Location $expectedLocation): void
    {
        $request = ServerRequestFactory::fromGlobals();
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        self::assertEquals($expectedLocation, $this->resolver->resolveFromRequest($request));
    }

    public static function provideHeaders(): iterable
    {
        yield 'all headers' => [
            [
                'cf-ipcountry' => 'US',
                'cf-ipcity' => 'Austin',
                'cf-region' => 'Texas',
                'cf-iplatitude' => '30.27130',
                'cf-iplongitude' => '-97.74260',
                'cf-timezone' => 'America/Chicago',
            ],
            new Location(
                countryCode: 'US',
                countryName: 'United States',
                regionName: 'Texas',
                city: 'Austin',
                latitude: 30.2713,
                longitude: -97.7426,
                timeZone: 'America/Chicago',
            ),
        ];
        yield 'only country' => [['CF-IPCountry' => 'es'], new Location(countryCode: 'ES', countryName: 'Spain')];
        yield 'invalid coordinates' => [
            ['CF-IPCountry' => 'GB', 'CF-IPLatitude' => 'foo', 'CF-IPLongitude' => ''],
            new Location(countryCode: 'GB', countryName: 'United Kingdom'),
        ];
        yield 'unknown country code' => [['CF-IPCountry' => 'QQ'], new Location(countryCode: 'QQ')];
    }
}
