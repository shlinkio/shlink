<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\RedirectRule;

use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Symfony\Component\Console\Style\StyleInterface;

interface RedirectRuleHandlerInterface
{
    /**
     * Interactively manages provided list of rules and applies changes to it
     *
     * @param ShortUrlRedirectRule[] $rules
     * @return ShortUrlRedirectRule[]|null - A new list of rules to save, or null if no changes should be saved
     */
    public function manageRules(StyleInterface $io, ShortUrl $shortUrl, array $rules): ?array;
}
