<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

readonly class ShortUrlRedirectionResolver implements ShortUrlRedirectionResolverInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function resolveLongUrl(ShortUrl $shortUrl, ServerRequestInterface $request): string
    {
        $rules = $this->em->getRepository(ShortUrlRedirectRule::class)->findBy(
            criteria: ['shortUrl' => $shortUrl],
            orderBy: ['priority' => 'ASC'],
        );
        foreach ($rules as $rule) {
            // Return the long URL for the first rule found that matches
            if ($rule->matchesRequest($request)) {
                return $rule->longUrl;
            }
        }

        return $shortUrl->getLongUrl();
    }
}
