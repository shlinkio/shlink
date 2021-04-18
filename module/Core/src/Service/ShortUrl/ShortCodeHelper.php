<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;

class ShortCodeHelper implements ShortCodeHelperInterface // TODO Rename to ShortCodeUniquenessHelper
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function ensureShortCodeUniqueness(ShortUrl $shortUrlToBeCreated, bool $hasCustomSlug): bool
    {
        $shortCode = $shortUrlToBeCreated->getShortCode();
        $domain = $shortUrlToBeCreated->getDomain();
        $domainAuthority = $domain !== null ? $domain->getAuthority() : null;

        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $otherShortUrlsExist = $repo->shortCodeIsInUse($shortCode, $domainAuthority);

        if (! $otherShortUrlsExist) {
            return true;
        }

        if ($hasCustomSlug) {
            return false;
        }

        $shortUrlToBeCreated->regenerateShortCode();
        return $this->ensureShortCodeUniqueness($shortUrlToBeCreated, $hasCustomSlug);
    }
}
