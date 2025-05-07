<?php

namespace ShlinkioTest\Shlink\Core\RedirectRule\Entity;

use Cake\Chronos\Chronos;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Entity\RedirectCondition;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectCondition;
use Shlinkio\Shlink\IpGeolocation\Model\Location;

use const Shlinkio\Shlink\IP_ADDRESS_REQUEST_ATTRIBUTE;
use const ShlinkioTest\Shlink\ANDROID_USER_AGENT;
use const ShlinkioTest\Shlink\CHROMEOS_USER_AGENT;
use const ShlinkioTest\Shlink\IOS_USER_AGENT;
use const ShlinkioTest\Shlink\LINUX_USER_AGENT;
use const ShlinkioTest\Shlink\MACOS_USER_AGENT;
use const ShlinkioTest\Shlink\WINDOWS_USER_AGENT;

class RedirectConditionTest extends TestCase
{
    #[Test]
    #[TestWith(['nop', '', false])] // param not present
    #[TestWith(['foo', 'not-bar', false])] // param present with wrong value
    #[TestWith(['foo', 'bar', true])] // param present with correct value
    public function matchesQueryParams(string $param, string $value, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['foo' => 'bar']);
        $result = RedirectCondition::forQueryParam($param, $value)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    #[TestWith(['nop', '', false])] // param not present
    #[TestWith(['foo', '', true])]
    #[TestWith(['foo', 'something', true])]
    #[TestWith(['foo', 'something else', true])]
    public function matchesAnyValueQueryParams(string $param, string $value, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['foo' => $value]);
        $result = RedirectCondition::forAnyValueQueryParam($param)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    #[TestWith(['nop', '', false])] // param not present
    #[TestWith(['foo', '', true])]
    #[TestWith(['foo', null, true])]
    #[TestWith(['foo', 'something', false])]
    #[TestWith(['foo', 'something else', false])]
    public function matchesValuelessQueryParams(string $param, string|null $value, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals()->withQueryParams(['foo' => $value]);
        $result = RedirectCondition::forValuelessQueryParam($param)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    #[TestWith([null, '', false], 'no accept language')]
    #[TestWith(['', '', false], 'empty accept language')]
    #[TestWith(['*', '', false], 'wildcard accept language')]
    #[TestWith(['en', 'en', true], 'single language match')]
    #[TestWith(['es, en,fr', 'en', true], 'multiple languages match')]
    #[TestWith(['es, en-US,fr', 'EN', true], 'multiple locales match')]
    #[TestWith(['es_ES', 'es-ES', true], 'single locale match')]
    #[TestWith(['en-US,es-ES;q=0.6', 'es-ES', false], 'too low quality')]
    #[TestWith(['en-US,es-ES;q=0.9', 'es-ES', true], 'quality high enough')]
    #[TestWith(['en-UK', 'en-uk', true], 'different casing match')]
    #[TestWith(['en-UK', 'en', true], 'only lang')]
    #[TestWith(['es-AR', 'en', false], 'different only lang')]
    #[TestWith(['fr', 'fr-FR', false], 'less restrictive matching locale')]
    public function matchesLanguage(string|null $acceptLanguage, string $value, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($acceptLanguage !== null) {
            $request = $request->withHeader('Accept-Language', $acceptLanguage);
        }

        $result = RedirectCondition::forLanguage($value)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[TestWith([null, DeviceType::ANDROID, false])]
    #[TestWith(['unknown', DeviceType::ANDROID, false])]
    #[TestWith([ANDROID_USER_AGENT, DeviceType::ANDROID, true])]
    #[TestWith([WINDOWS_USER_AGENT, DeviceType::DESKTOP, true])]
    #[TestWith([LINUX_USER_AGENT, DeviceType::DESKTOP, true])]
    #[TestWith([MACOS_USER_AGENT, DeviceType::DESKTOP, true])]
    #[TestWith([CHROMEOS_USER_AGENT, DeviceType::DESKTOP, true])]
    #[TestWith([WINDOWS_USER_AGENT, DeviceType::WINDOWS, true])]
    #[TestWith([LINUX_USER_AGENT, DeviceType::LINUX, true])]
    #[TestWith([MACOS_USER_AGENT, DeviceType::MACOS, true])]
    #[TestWith([CHROMEOS_USER_AGENT, DeviceType::CHROMEOS, true])]
    #[TestWith([IOS_USER_AGENT, DeviceType::IOS, true])]
    #[TestWith([IOS_USER_AGENT, DeviceType::MOBILE, true])]
    #[TestWith([ANDROID_USER_AGENT, DeviceType::MOBILE, true])]
    #[TestWith([IOS_USER_AGENT, DeviceType::ANDROID, false])]
    #[TestWith([WINDOWS_USER_AGENT, DeviceType::IOS, false])]
    #[TestWith([LINUX_USER_AGENT, DeviceType::WINDOWS, false])]
    public function matchesDevice(string|null $userAgent, DeviceType $value, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($userAgent !== null) {
            $request = $request->withHeader('User-Agent', $userAgent);
        }

        $result = RedirectCondition::forDevice($value)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }

    #[Test]
    #[TestWith([null, '1.2.3.4', false], 'no remote IP address')]
    #[TestWith(['1.2.3.4', '1.2.3.4', true], 'static IP address match')]
    #[TestWith(['4.3.2.1', '1.2.3.4', false], 'no static IP address match')]
    #[TestWith(['192.168.1.35', '192.168.1.0/24', true], 'CIDR block match')]
    #[TestWith(['1.2.3.4', '192.168.1.0/24', false], 'no CIDR block match')]
    #[TestWith(['192.168.1.35', '192.168.1.*', true], 'wildcard pattern match')]
    #[TestWith(['1.2.3.4', '192.168.1.*', false], 'no wildcard pattern match')]
    public function matchesRemoteIpAddress(string|null $remoteIp, string $ipToMatch, bool $expected): void
    {
        $request = ServerRequestFactory::fromGlobals();
        if ($remoteIp !== null) {
            $request = $request->withAttribute(IP_ADDRESS_REQUEST_ATTRIBUTE, $remoteIp);
        }

        $result = RedirectCondition::forIpAddress($ipToMatch)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }

    #[Test, DataProvider('provideVisitsWithCountry')]
    public function matchesGeolocationCountryCode(
        Location|null $location,
        string $countryCodeToMatch,
        bool $expected,
    ): void {
        $request = ServerRequestFactory::fromGlobals()->withAttribute(Location::class, $location);
        $result = RedirectCondition::forGeolocationCountryCode($countryCodeToMatch)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }
    public static function provideVisitsWithCountry(): iterable
    {
        yield 'no location' => [null, 'US', false];
        yield 'non-matching location' => [new Location(countryCode: 'ES'), 'US', false];
        yield 'matching location' => [new Location(countryCode: 'US'), 'US', true];
        yield 'matching case-insensitive' => [new Location(countryCode: 'US'), 'us', true];
    }

    #[Test, DataProvider('provideVisitsWithCity')]
    public function matchesGeolocationCityName(
        Location|null $location,
        string $cityNameToMatch,
        bool $expected,
    ): void {
        $request = ServerRequestFactory::fromGlobals()->withAttribute(Location::class, $location);
        $result = RedirectCondition::forGeolocationCityName($cityNameToMatch)->matchesRequest($request);

        self::assertEquals($expected, $result);
    }
    public static function provideVisitsWithCity(): iterable
    {
        yield 'no location' => [null, 'New York', false];
        yield 'non-matching location' => [new Location(city: 'Los Angeles'), 'New York', false];
        yield 'matching location' => [new Location(city: 'Madrid'), 'Madrid', true];
        yield 'matching case-insensitive' => [new Location(city: 'Los Angeles'), 'los angeles', true];
    }

    #[Test]
    #[TestWith(['invalid', null])]
    #[TestWith([RedirectConditionType::DEVICE->value, RedirectConditionType::DEVICE])]
    #[TestWith([RedirectConditionType::LANGUAGE->value, RedirectConditionType::LANGUAGE])]
    #[TestWith([RedirectConditionType::QUERY_PARAM->value, RedirectConditionType::QUERY_PARAM])]
    #[TestWith([RedirectConditionType::ANY_VALUE_QUERY_PARAM->value, RedirectConditionType::ANY_VALUE_QUERY_PARAM])]
    #[TestWith([RedirectConditionType::VALUELESS_QUERY_PARAM->value, RedirectConditionType::VALUELESS_QUERY_PARAM])]
    #[TestWith([RedirectConditionType::IP_ADDRESS->value, RedirectConditionType::IP_ADDRESS])]
    #[TestWith(
        [RedirectConditionType::GEOLOCATION_COUNTRY_CODE->value, RedirectConditionType::GEOLOCATION_COUNTRY_CODE],
    )]
    #[TestWith([RedirectConditionType::GEOLOCATION_CITY_NAME->value, RedirectConditionType::GEOLOCATION_CITY_NAME])]
    public function canBeCreatedFromImport(string $type, RedirectConditionType|null $expectedType): void
    {
        $condition = RedirectCondition::fromImport(
            new ImportedShlinkRedirectCondition($type, DeviceType::ANDROID->value, ''),
        );
        self::assertEquals($expectedType, $condition?->type);
    }

    #[Test, DataProvider('provideVisitsWithBeforeDateCondition')]
    public function matchesBeforeDate(string $date, bool $expectedResult): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $result = RedirectCondition::forBeforeDate($date)->matchesRequest($request);

        self::assertEquals($expectedResult, $result);
    }

    public static function provideVisitsWithBeforeDateCondition(): iterable
    {
        yield 'date later than current' => [Chronos::now()->addHours(1)->toIso8601String(), true];
        yield 'date earlier than current' => [Chronos::now()->subHours(1)->toIso8601String(), false];
    }
}
