<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

interface ShortUrlRedirectRuleServiceInterface
{
    /**
     * @return ShortUrlRedirectRule[]
     */
    public function rulesForShortUrl(ShortUrl $shortUrl): array;
}
