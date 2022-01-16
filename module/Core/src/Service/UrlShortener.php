<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;

class UrlShortener implements UrlShortenerInterface
{
    public function __construct(
        private ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private EntityManagerInterface $em,
        private ShortUrlRelationResolverInterface $relationResolver,
        private ShortCodeUniquenessHelperInterface $shortCodeHelper,
    ) {
    }

    /**
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     */
    public function shorten(ShortUrlMeta $meta): ShortUrl
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        /** @var ShortUrlMeta $meta */
        $meta = $this->titleResolutionHelper->processTitleAndValidateUrl($meta);

        return $this->em->transactional(function () use ($meta) {
            $shortUrl = ShortUrl::fromMeta($meta, $this->relationResolver);

            $this->verifyShortCodeUniqueness($meta, $shortUrl);
            $this->em->persist($shortUrl);

            return $shortUrl;
        });
    }

    private function findExistingShortUrlIfExists(ShortUrlMeta $meta): ?ShortUrl
    {
        if (! $meta->findIfExists()) {
            return null;
        }

        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        return $repo->findOneMatching($meta);
    }

    private function verifyShortCodeUniqueness(ShortUrlMeta $meta, ShortUrl $shortUrlToBeCreated): void
    {
        $couldBeMadeUnique = $this->shortCodeHelper->ensureShortCodeUniqueness(
            $shortUrlToBeCreated,
            $meta->hasCustomSlug(),
        );

        if (! $couldBeMadeUnique) {
            $domain = $shortUrlToBeCreated->getDomain();
            $domainAuthority = $domain?->getAuthority();

            throw NonUniqueSlugException::fromSlug($shortUrlToBeCreated->getShortCode(), $domainAuthority);
        }
    }
}
