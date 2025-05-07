<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Cake\Chronos\Chronos;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectCondition;

use function array_key_exists;
use function Shlinkio\Shlink\Core\acceptLanguageToLocales;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function Shlinkio\Shlink\Core\geolocationFromRequest;
use function Shlinkio\Shlink\Core\ipAddressFromRequest;
use function Shlinkio\Shlink\Core\normalizeLocale;
use function Shlinkio\Shlink\Core\splitLocale;
use function sprintf;
use function strcasecmp;
use function trim;

class RedirectCondition extends AbstractEntity implements JsonSerializable
{
    private function __construct(
        public readonly RedirectConditionType $type,
        private readonly string|null $matchValue = null,
        private readonly string|null $matchKey = null,
    ) {
    }

    public static function forQueryParam(string $param, string $value): self
    {
        return new self(RedirectConditionType::QUERY_PARAM, $value, $param);
    }

    public static function forAnyValueQueryParam(string $param): self
    {
        return new self(RedirectConditionType::ANY_VALUE_QUERY_PARAM, matchKey: $param);
    }

    public static function forValuelessQueryParam(string $param): self
    {
        return new self(RedirectConditionType::VALUELESS_QUERY_PARAM, matchKey: $param);
    }

    public static function forLanguage(string $language): self
    {
        return new self(RedirectConditionType::LANGUAGE, $language);
    }

    public static function forDevice(DeviceType $device): self
    {
        return new self(RedirectConditionType::DEVICE, $device->value);
    }

    /**
     * @param string $ipAddressPattern - A static IP address (100.200.80.40), CIDR block (192.168.10.0/24) or wildcard
     *                                   pattern (11.22.*.*)
     */
    public static function forIpAddress(string $ipAddressPattern): self
    {
        return new self(RedirectConditionType::IP_ADDRESS, $ipAddressPattern);
    }

    public static function forGeolocationCountryCode(string $countryCode): self
    {
        return new self(RedirectConditionType::GEOLOCATION_COUNTRY_CODE, $countryCode);
    }

    public static function forGeolocationCityName(string $cityName): self
    {
        return new self(RedirectConditionType::GEOLOCATION_CITY_NAME, $cityName);
    }

    public static function forBeforeDate(string $date): self
    {
        return new self(RedirectConditionType::BEFORE_DATE, $date);
    }

    public static function fromRawData(array $rawData): self
    {
        $type = RedirectConditionType::from($rawData[RedirectRulesInputFilter::CONDITION_TYPE]);
        $value = $rawData[RedirectRulesInputFilter::CONDITION_MATCH_VALUE];
        $key = $rawData[RedirectRulesInputFilter::CONDITION_MATCH_KEY] ?? null;

        return new self($type, $value, $key);
    }

    public static function fromImport(ImportedShlinkRedirectCondition $cond): self|null
    {
        $type = RedirectConditionType::tryFrom($cond->type);
        if ($type === null) {
            return null;
        }

        return match ($type) {
            RedirectConditionType::QUERY_PARAM => self::forQueryParam($cond->matchKey ?? '', $cond->matchValue),
            RedirectConditionType::ANY_VALUE_QUERY_PARAM => self::forAnyValueQueryParam($cond->matchValue),
            RedirectConditionType::VALUELESS_QUERY_PARAM => self::forValuelessQueryParam($cond->matchValue),
            RedirectConditionType::LANGUAGE => self::forLanguage($cond->matchValue),
            RedirectConditionType::DEVICE => self::forDevice(DeviceType::from($cond->matchValue)),
            RedirectConditionType::IP_ADDRESS => self::forIpAddress($cond->matchValue),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => self::forGeolocationCountryCode($cond->matchValue),
            RedirectConditionType::GEOLOCATION_CITY_NAME => self::forGeolocationCityName($cond->matchValue),
            RedirectConditionType::BEFORE_DATE => self::forBeforeDate($cond->matchValue),
        };
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return match ($this->type) {
            RedirectConditionType::QUERY_PARAM => $this->matchesQueryParam($request),
            RedirectConditionType::ANY_VALUE_QUERY_PARAM => $this->matchesAnyValueQueryParam($request),
            RedirectConditionType::VALUELESS_QUERY_PARAM => $this->matchesValuelessQueryParam($request),
            RedirectConditionType::LANGUAGE => $this->matchesLanguage($request),
            RedirectConditionType::DEVICE => $this->matchesDevice($request),
            RedirectConditionType::IP_ADDRESS => $this->matchesRemoteIpAddress($request),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => $this->matchesGeolocationCountryCode($request),
            RedirectConditionType::GEOLOCATION_CITY_NAME => $this->matchesGeolocationCityName($request),
            RedirectConditionType::BEFORE_DATE => $this->matchesBeforeDate(),
        };
    }

    private function matchesQueryParam(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();
        $queryValue = $query[$this->matchKey] ?? null;

        return $queryValue === $this->matchValue;
    }

    private function matchesValuelessQueryParam(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();
        return $this->matchKey !== null && array_key_exists($this->matchKey, $query) && empty($query[$this->matchKey]);
    }

    private function matchesAnyValueQueryParam(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();
        return $this->matchKey !== null && array_key_exists($this->matchKey, $query);
    }

    private function matchesLanguage(ServerRequestInterface $request): bool
    {
        $acceptLanguage = trim($request->getHeaderLine('Accept-Language'));
        if ($acceptLanguage === '' || $acceptLanguage === '*' || $this->matchValue === null) {
            return false;
        }

        $acceptedLanguages = acceptLanguageToLocales($acceptLanguage, minQuality: 0.9);
        [$matchLanguage, $matchCountryCode] = splitLocale(normalizeLocale($this->matchValue));

        return some(
            $acceptedLanguages,
            static function (string $lang) use ($matchLanguage, $matchCountryCode): bool {
                [$language, $countryCode] = splitLocale($lang);

                if ($matchLanguage !== $language) {
                    return false;
                }

                return $matchCountryCode === null || $matchCountryCode === $countryCode;
            },
        );
    }

    private function matchesDevice(ServerRequestInterface $request): bool
    {
        $devices = DeviceType::matchFromUserAgent($request->getHeaderLine('User-Agent'));
        return some($devices, fn (DeviceType $device) => $device->value === $this->matchValue);
    }

    private function matchesRemoteIpAddress(ServerRequestInterface $request): bool
    {
        $remoteAddress = ipAddressFromRequest($request);
        return (
            $this->matchValue !== null
            && $remoteAddress !== null
            && IpAddressUtils::ipAddressMatchesGroups($remoteAddress, [$this->matchValue])
        );
    }

    private function matchesGeolocationCountryCode(ServerRequestInterface $request): bool
    {
        $geolocation = geolocationFromRequest($request);
        if ($geolocation === null || $this->matchValue === null) {
            return false;
        }

        return strcasecmp($geolocation->countryCode, $this->matchValue) === 0;
    }

    private function matchesGeolocationCityName(ServerRequestInterface $request): bool
    {
        $geolocation = geolocationFromRequest($request);
        if ($geolocation === null || $this->matchValue === null) {
            return false;
        }

        return strcasecmp($geolocation->city, $this->matchValue) === 0;
    }

    private function matchesBeforeDate(): bool
    {
        return Chronos::now()->lessThan(Chronos::parse($this->matchValue));
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'matchKey' => $this->matchKey,
            'matchValue' => $this->matchValue,
        ];
    }

    public function toHumanFriendly(): string
    {
        return match ($this->type) {
            RedirectConditionType::DEVICE => sprintf('device is %s', $this->matchValue),
            RedirectConditionType::LANGUAGE => sprintf('%s language is accepted', $this->matchValue),
            RedirectConditionType::QUERY_PARAM => sprintf(
                'query string contains %s=%s',
                $this->matchKey,
                $this->matchValue,
            ),
            RedirectConditionType::ANY_VALUE_QUERY_PARAM => sprintf(
                'query string contains %s param',
                $this->matchValue,
            ),
            RedirectConditionType::VALUELESS_QUERY_PARAM => sprintf(
                'query string contains %s param without a value (https://example.com?foo)',
                $this->matchValue,
            ),
            RedirectConditionType::IP_ADDRESS => sprintf('IP address matches %s', $this->matchValue),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => sprintf('country code is %s', $this->matchValue),
            RedirectConditionType::GEOLOCATION_CITY_NAME => sprintf('city name is %s', $this->matchValue),
            RedirectConditionType::BEFORE_DATE => sprintf('date before %s', $this->matchValue),
        };
    }
}
