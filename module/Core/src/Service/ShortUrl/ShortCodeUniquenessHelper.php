<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;

class ShortCodeUniquenessHelper implements ShortCodeUniquenessHelperInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function ensureShortCodeUniqueness(ShortUrl $shortUrlToBeCreated, bool $hasCustomSlug): bool
    {
        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $otherShortUrlsExist = $repo->shortCodeIsInUseWithLock(ShortUrlIdentifier::fromShortUrl($shortUrlToBeCreated));

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
