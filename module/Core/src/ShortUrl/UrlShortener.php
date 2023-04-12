<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
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
    public function shorten(ShortUrlCreation $creation): UrlShorteningResult
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($creation);
        if ($existingShortUrl !== null) {
            return UrlShorteningResult::withoutErrorOnEventDispatching($existingShortUrl);
        }

        $creation = $this->titleResolutionHelper->processTitleAndValidateUrl($creation);

        /** @var ShortUrl $newShortUrl */
        $newShortUrl = $this->em->wrapInTransaction(function () use ($creation): ShortUrl {
            $shortUrl = ShortUrl::create($creation, $this->relationResolver);

            $this->verifyShortCodeUniqueness($creation, $shortUrl);
            $this->em->persist($shortUrl);

            return $shortUrl;
        });

        try {
            $this->eventDispatcher->dispatch(new ShortUrlCreated($newShortUrl->getId()));
        } catch (ContainerExceptionInterface $e) {
            // Ignore container errors when dispatching the event.
            // When using openswoole, this event will try to enqueue a task, which cannot be done outside an HTTP
            // request.
            // If the short URL is created from CLI, the event dispatching will fail.
            return UrlShorteningResult::withErrorOnEventDispatching($newShortUrl, $e);
        }

        return UrlShorteningResult::withoutErrorOnEventDispatching($newShortUrl);
    }

    private function findExistingShortUrlIfExists(ShortUrlCreation $creation): ?ShortUrl
    {
        if (! $creation->findIfExists) {
            return null;
        }

        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        return $repo->findOneMatching($creation);
    }

    private function verifyShortCodeUniqueness(ShortUrlCreation $meta, ShortUrl $shortUrlToBeCreated): void
    {
        $couldBeMadeUnique = $this->shortCodeHelper->ensureShortCodeUniqueness(
            $shortUrlToBeCreated,
            $meta->hasCustomSlug(),
        );

        if (! $couldBeMadeUnique) {
            $domain = $shortUrlToBeCreated->getDomain();
            $domainAuthority = $domain?->authority;

            throw NonUniqueSlugException::fromSlug($shortUrlToBeCreated->getShortCode(), $domainAuthority);
        }
    }
}
