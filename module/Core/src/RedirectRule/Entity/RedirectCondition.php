<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use JsonSerializable;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\Model\DeviceType;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;
use Shlinkio\Shlink\Core\RedirectRule\Model\Validation\RedirectRulesInputFilter;

use function Shlinkio\Shlink\Core\acceptLanguageToLocales;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function Shlinkio\Shlink\Core\normalizeLocale;
use function Shlinkio\Shlink\Core\splitLocale;
use function sprintf;
use function strtolower;
use function trim;

class RedirectCondition extends AbstractEntity implements JsonSerializable
{
    private function __construct(
        private readonly RedirectConditionType $type,
        private readonly string $matchValue,
        private readonly ?string $matchKey = null,
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

    public static function fromRawData(array $rawData): self
    {
        $type = RedirectConditionType::from($rawData[RedirectRulesInputFilter::CONDITION_TYPE]);
        $value = $rawData[RedirectRulesInputFilter::CONDITION_MATCH_VALUE];
        $key = $rawData[RedirectRulesInputFilter::CONDITION_MATCH_KEY] ?? null;

        return new self($type, $value, $key);
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return match ($this->type) {
            RedirectConditionType::QUERY_PARAM => $this->matchesQueryParam($request),
            RedirectConditionType::LANGUAGE => $this->matchesLanguage($request),
            RedirectConditionType::DEVICE => $this->matchesDevice($request),
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

        $acceptedLanguages = acceptLanguageToLocales($acceptLanguage);
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
        return $device !== null && $device->value === strtolower($this->matchValue);
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
        };
    }
}
