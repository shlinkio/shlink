<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shlinkio\Shlink\Core\EventDispatcher\Event\ShortUrlCreated;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortCodeUniquenessHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlTitleResolutionHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlCreation;
use Shlinkio\Shlink\Core\ShortUrl\Model\UrlShorteningResult;
use Shlinkio\Shlink\Core\ShortUrl\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;

readonly class UrlShortener implements UrlShortenerInterface
{
    public function __construct(
        private ShortUrlTitleResolutionHelperInterface $titleResolutionHelper,
        private EntityManagerInterface $em,
        private ShortUrlRelationResolverInterface $relationResolver,
        private ShortCodeUniquenessHelperInterface $shortCodeHelper,
        private EventDispatcherInterface $eventDispatcher,
        private ShortUrlRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws NonUniqueSlugException
     */
    public function shorten(ShortUrlCreation $creation): UrlShorteningResult
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($creation);
        if ($existingShortUrl !== null) {
            return UrlShorteningResult::withoutErrorOnEventDispatching($existingShortUrl);
        }

        $creation = $this->titleResolutionHelper->processTitle($creation);

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
            // When using RoadRunner, this event will try to enqueue a task, which cannot be done outside an HTTP
            // request.
            // If the short URL is created from CLI, the event dispatching will fail.
            return UrlShorteningResult::withErrorOnEventDispatching($newShortUrl, $e);
        }

        return UrlShorteningResult::withoutErrorOnEventDispatching($newShortUrl);
    }

    private function findExistingShortUrlIfExists(ShortUrlCreation $creation): ShortUrl|null
    {
        if (! $creation->findIfExists) {
            return null;
        }

        return $this->repo->findOneMatching($creation);
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
