<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
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
    public function resolveShortUrl(ShortUrlIdentifier $identifier): ShortUrl
    {
        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOneByShortCode($identifier->shortCode(), $identifier->domain());
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
        $shortUrl = $this->resolveShortUrl($identifier);
        if (! $shortUrl->isEnabled()) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }
}
