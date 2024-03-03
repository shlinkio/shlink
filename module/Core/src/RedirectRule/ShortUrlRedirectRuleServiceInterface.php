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
     * @return ShortUrlRedirectRule[]
     */
    public function setRulesForShortUrl(ShortUrl $shortUrl, RedirectRulesData $data): array;

    /**
     * @param ShortUrlRedirectRule[] $rules
     */
    public function saveRulesForShortUrl(ShortUrl $shortUrl, array $rules): void;
}
