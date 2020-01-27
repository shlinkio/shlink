<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;

class ShortUrlResolver implements ShortUrlResolverInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function shortCodeToShortUrl(string $shortCode, ?string $domain = null): ShortUrl
    {
        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOneByShortCode($shortCode, $domain);
        if ($shortUrl === null) {
            throw ShortUrlNotFoundException::fromNotFoundShortCode($shortCode, $domain);
        }

        return $shortUrl;
    }

    /**
     * @throws ShortUrlNotFoundException
     */
    public function shortCodeToEnabledShortUrl(string $shortCode, ?string $domain = null): ShortUrl
    {
        $shortUrl = $this->shortCodeToShortUrl($shortCode, $domain);
        if (! $shortUrl->isEnabled()) {
            throw ShortUrlNotFoundException::fromNotFoundShortCode($shortCode, $domain);
        }

        return $shortUrl;
    }
}
