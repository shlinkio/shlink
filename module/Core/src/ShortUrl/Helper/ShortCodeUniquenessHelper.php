<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;

readonly class ShortCodeUniquenessHelper implements ShortCodeUniquenessHelperInterface
{
    public function __construct(private ShortUrlRepositoryInterface $repo, private UrlShortenerOptions $options)
    {
    }

    public function ensureShortCodeUniqueness(ShortUrl $shortUrlToBeCreated, bool $hasCustomSlug): bool
    {
        $identifier = ShortUrlIdentifier::fromShortUrl($shortUrlToBeCreated);
        $otherShortUrlsExist = $this->repo->shortCodeIsInUseWithLock($identifier);

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
