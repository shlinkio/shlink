<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

class ShortUrlResolver implements ShortUrlResolverInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
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
        $shortUrl = $shortUrlRepo->findOneWithDomainFallback($identifier);
        if (! $shortUrl?->isEnabled()) {
            throw ShortUrlNotFoundException::fromNotFound($identifier);
        }

        return $shortUrl;
    }
}
