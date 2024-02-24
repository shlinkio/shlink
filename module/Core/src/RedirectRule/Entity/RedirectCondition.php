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
        if ($this->type === RedirectConditionType::QUERY_PARAM && $this->matchKey !== null) {
            $query = $request->getQueryParams();
            $queryValue = $query[$this->matchKey] ?? null;
            return $queryValue === $this->matchValue;
        }

        if ($this->type === RedirectConditionType::LANGUAGE && $request->hasHeader('Accept-Language')) {
            $acceptedLanguages = explode(',', $request->getHeaderLine('Accept-Language'));
            $normalizedLanguage = normalizeLocale($this->matchValue);

            return some(
                $acceptedLanguages,
                static fn (string $lang) => normalizeLocale($lang) === $normalizedLanguage,
            );
        }

        return false;
    }
}
