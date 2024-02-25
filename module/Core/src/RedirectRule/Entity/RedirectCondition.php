<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;

use function explode;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function Shlinkio\Shlink\Core\normalizeLocale;
use function sprintf;

class RedirectCondition extends AbstractEntity
{
    private function __construct(
        public readonly string $name,
        private readonly RedirectConditionType $type,
        public readonly string $matchValue,
        public readonly ?string $matchKey = null,
    ) {
    }

    public static function forQueryParam(string $param, string $value): self
    {
        $type = RedirectConditionType::QUERY_PARAM;
        $name = sprintf('%s-%s-%s', $type->value, $param, $value);

        return new self($name, $type, $value, $param);
    }

    public static function forLanguage(string $language): self
    {
        $type = RedirectConditionType::LANGUAGE;
        $name = sprintf('%s-%s', $type->value, $language);

        return new self($name, $type, $language);
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return match ($this->type) {
            RedirectConditionType::QUERY_PARAM => $this->matchesQueryParam($request),
            RedirectConditionType::LANGUAGE => $this->matchesLanguage($request),
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
        $acceptLanguage = $request->getHeaderLine('Accept-Language');
        if ($acceptLanguage === '' || $acceptLanguage === '*') {
            return false;
        }

        $acceptedLanguages = explode(',', $acceptLanguage);
        $normalizedLanguage = normalizeLocale($this->matchValue);

        return some(
            $acceptedLanguages,
            static fn (string $lang) => normalizeLocale($lang) === $normalizedLanguage,
        );
    }
}
