<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;

class ShortCodeUniquenessHelper implements ShortCodeUniquenessHelperInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlShortenerOptions $options,
    ) {
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

        $shortUrlToBeCreated->regenerateShortCode($this->options->mode);
        return $this->ensureShortCodeUniqueness($shortUrlToBeCreated, $hasCustomSlug);
    }
}
