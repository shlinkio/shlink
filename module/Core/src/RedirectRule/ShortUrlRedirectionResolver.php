<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

readonly class ShortUrlRedirectionResolver implements ShortUrlRedirectionResolverInterface
{
    public function __construct(private ShortUrlRedirectRuleServiceInterface $ruleService)
    {
    }

    public function resolveLongUrl(ShortUrl $shortUrl, ServerRequestInterface $request): string
    {
        $rules = $this->ruleService->rulesForShortUrl($shortUrl);
        foreach ($rules as $rule) {
            // Return the long URL for the first rule found that matches
            if ($rule->matchesRequest($request, $shortUrl)) {
                return $rule->longUrl;
            }
        }

        return $shortUrl->getLongUrl();
    }
}
