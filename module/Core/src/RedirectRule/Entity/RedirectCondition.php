<?php

namespace Shlinkio\Shlink\Core\RedirectRule\Entity;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Entity\AbstractEntity;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectConditionType;

use function explode;
use function Shlinkio\Shlink\Core\ArrayUtils\some;
use function Shlinkio\Shlink\Core\normalizeLocale;

class RedirectCondition extends AbstractEntity
{
    public function __construct(
        public readonly string $name,
        public readonly RedirectConditionType $type,
        public readonly string $matchValue,
        public readonly ?string $matchKey = null,
    ) {
    }

    /**
     * Tells if this condition matches provided request
     */
    public function matchesRequest(ServerRequestInterface $request): bool
    {
        return match ($this->type) {
            RedirectConditionType::QUERY_PARAM => $this->matchesQueryParam($request),
            RedirectConditionType::LANGUAGE => $this->matchesLanguage($request),
            default => false,
        };
    }

    public function matchesQueryParam(ServerRequestInterface $request): bool
    {
        if ($this->matchKey !== null) {
            return false;
        }

        $query = $request->getQueryParams();
        $queryValue = $query[$this->matchKey] ?? null;

        return $queryValue === $this->matchValue;
    }

    public function matchesLanguage(ServerRequestInterface $request): bool
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
