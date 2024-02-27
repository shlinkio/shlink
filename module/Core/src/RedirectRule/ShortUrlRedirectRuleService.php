<?php

namespace Shlinkio\Shlink\Core\RedirectRule;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\RedirectRule\Entity\ShortUrlRedirectRule;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;

readonly class ShortUrlRedirectRuleService implements ShortUrlRedirectRuleServiceInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @return ShortUrlRedirectRule[]
     */
    public function rulesForShortUrl(ShortUrl $shortUrl): array
    {
        return $this->em->getRepository(ShortUrlRedirectRule::class)->findBy(
            criteria: ['shortUrl' => $shortUrl],
            orderBy: ['priority' => 'ASC'],
        );
    }
}
