<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Cake\Chronos\Chronos;
use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\AgeMatch;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;
use Shlinkio\Shlink\Importer\Model\ImportedShlinkRedirectCondition;

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
        private readonly string $matchValue,
        private readonly string|null $matchKey = null,
    ) {
    }

    public static function forQueryParam(string $param, string $value): self
    {
        return new self(RedirectConditionType::QUERY_PARAM, $value, $param);
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

    public static function forAge(AgeMatch $ageMatch, string $ageSeconds): self
    {
        return new self(RedirectConditionType::AGE, $ageSeconds, $ageMatch->value);
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
            RedirectConditionType::LANGUAGE => self::forLanguage($cond->matchValue),
            RedirectConditionType::DEVICE => self::forDevice(DeviceType::from($cond->matchValue)),
            RedirectConditionType::IP_ADDRESS => self::forIpAddress($cond->matchValue),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => self::forGeolocationCountryCode($cond->matchValue),
            RedirectConditionType::GEOLOCATION_CITY_NAME => self::forGeolocationCityName($cond->matchValue),
            RedirectConditionType::AGE => self::forAge(AgeMatch::from($cond->matchKey), $cond->matchValue),
        };
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request, ShortUrl|null $shortUrl = null): bool
    {
        return match ($this->type) {
            RedirectConditionType::QUERY_PARAM => $this->matchesQueryParam($request),
            RedirectConditionType::LANGUAGE => $this->matchesLanguage($request),
            RedirectConditionType::DEVICE => $this->matchesDevice($request),
            RedirectConditionType::IP_ADDRESS => $this->matchesRemoteIpAddress($request),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => $this->matchesGeolocationCountryCode($request),
            RedirectConditionType::GEOLOCATION_CITY_NAME => $this->matchesGeolocationCityName($request),
            RedirectConditionType::AGE => $this->matchesAge($shortUrl),
        };
    }

    private function matchesQueryParam(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();
        $queryValue = $query[$this->matchKey] ?? null;

        return $queryValue === $this->matchValue;
    }

    private function matchesLanguage(ServerRequestInterface $request): bool
    {
        $acceptLanguage = trim($request->getHeaderLine('Accept-Language'));
        if ($acceptLanguage === '' || $acceptLanguage === '*') {
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
        $device = DeviceType::matchFromUserAgent($request->getHeaderLine('User-Agent'));
        return $device !== null && $device->value === $this->matchValue;
    }

    private function matchesRemoteIpAddress(ServerRequestInterface $request): bool
    {
        $remoteAddress = ipAddressFromRequest($request);
        return $remoteAddress !== null && IpAddressUtils::ipAddressMatchesGroups($remoteAddress, [$this->matchValue]);
    }

    private function matchesGeolocationCountryCode(ServerRequestInterface $request): bool
    {
        $geolocation = geolocationFromRequest($request);
        if ($geolocation === null) {
            return false;
        }

        return strcasecmp($geolocation->countryCode, $this->matchValue) === 0;
    }

    private function matchesGeolocationCityName(ServerRequestInterface $request): bool
    {
        $geolocation = geolocationFromRequest($request);
        if ($geolocation === null) {
            return false;
        }

        return strcasecmp($geolocation->city, $this->matchValue) === 0;
    }

    private function matchesAge(ShortUrl $shortUrl): bool
    {
        $ageSeconds = Chronos::now()->diffInSeconds($shortUrl->dateCreated());

        if ($this->matchKey === AgeMatch::OLDER->value && $ageSeconds > $this->matchValue){
            return true;
        }

        if ($this->matchKey === AgeMatch::YOUNGER->value && $ageSeconds < $this->matchValue){
            return true;
        }

        return false;
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
            RedirectConditionType::IP_ADDRESS => sprintf('IP address matches %s', $this->matchValue),
            RedirectConditionType::GEOLOCATION_COUNTRY_CODE => sprintf('country code is %s', $this->matchValue),
            RedirectConditionType::GEOLOCATION_CITY_NAME => sprintf('city name is %s', $this->matchValue),
            RedirectConditionType::AGE => sprintf(
                'link age %s %s seconds',
                $this->matchKey,
                $this->matchValue,
            ),
        };
    }
}
