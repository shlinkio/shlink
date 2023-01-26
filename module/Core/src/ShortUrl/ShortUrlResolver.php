<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlResolver implements ShortUrlResolverInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlShortenerOptions $urlShortenerOptions,
    ) {
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function resolveShortUrl(ShortUrlIdentifier $identifier, ?ApiKey $apiKey = null): ShortUrl
    {
        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOne($identifier, $apiKey?->spec());
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
        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOneWithDomainFallback($identifier, $this->urlShortenerOptions->mode);
        if (! $shortUrl?->isEnabled()) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }
}
