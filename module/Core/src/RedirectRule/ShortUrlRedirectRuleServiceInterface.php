<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\RedirectRule\Model\RedirectRulesData;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

interface ShortUrlRedirectRuleServiceInterface
{
    /**
     * @return ShortUrlRedirectRule[]
     */
    public function rulesForShortUrl(ShortUrl $shortUrl): array;

    /**
     * Resolve a set of redirect rules and attach them to a short URL, replacing any already existing rules.
     * @return ShortUrlRedirectRule[]
     */
    public function setRulesForShortUrl(ShortUrl $shortUrl, RedirectRulesData $data): array;

    /**
     * Save provided set of rules for a short URL, replacing any already existing rules.
     * @param ShortUrlRedirectRule[] $rules
     */
    public function saveRulesForShortUrl(ShortUrl $shortUrl, array $rules): void;
}
