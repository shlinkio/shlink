<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;

class UrlShortener implements UrlShortenerInterface
{
    public function __construct(
        private readonly ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private readonly EntityManagerInterface $em,
        private readonly ShortUrlRelationResolverInterface $relationResolver,
        private readonly ShortCodeUniquenessHelperInterface $shortCodeHelper,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     */
    public function shorten(ShortUrlCreation $meta): ShortUrl
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        /** @var \Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation $meta */
        $meta = $this->titleResolutionHelper->processTitleAndValidateUrl($meta);

        /** @var ShortUrl $newShortUrl */
        $newShortUrl = $this->em->wrapInTransaction(function () use ($meta) {
            $shortUrl = ShortUrl::fromMeta($meta, $this->relationResolver);

            $this->verifyShortCodeUniqueness($meta, $shortUrl);
            $this->em->persist($shortUrl);

            return $shortUrl;
        });

        $this->eventDispatcher->dispatch(new ShortUrlCreated($newShortUrl->getId()));

        return $newShortUrl;
    }

    private function findExistingShortUrlIfExists(ShortUrlCreation $meta): ?ShortUrl
    {
        if (! $meta->findIfExists()) {
            return null;
        }

        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        return $repo->findOneMatching($meta);
    }

    private function verifyShortCodeUniqueness(ShortUrlCreation $meta, ShortUrl $shortUrlToBeCreated): void
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
