<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

readonly class ShortUrlResolver implements ShortUrlResolverInterface
{
    public function __construct(
        private ShortUrlRepositoryInterface $repo,
        private UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveShortUrl(ShortUrlIdentifier $identifier, ApiKey|null $apiKey = null): ShortUrl
    {
        $shortUrl = $this->repo->findOne($identifier, $apiKey?->spec());
        if ($shortUrl === null) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveEnabledShortUrl(ShortUrlIdentifier $identifier): ShortUrl
    {
        $shortUrl = $this->resolvePublicShortUrl($identifier);
        if (! $shortUrl->isEnabled()) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolvePublicShortUrl(ShortUrlIdentifier $identifier): ShortUrl
    {
        $shortUrl = $this->repo->findOneWithDomainFallback($identifier, $this->urlShortenerOptions->mode);
        if ($shortUrl === null) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }
}
