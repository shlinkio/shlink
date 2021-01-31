<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeHelperInterface;
use Shlinkio\Shlink\Core\ShortUrl\Resolver\ShortUrlRelationResolverInterface;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;
use Throwable;

class UrlShortener implements UrlShortenerInterface
{
    private EntityManagerInterface $em;
    private UrlValidatorInterface $urlValidator;
    private ShortUrlRelationResolverInterface $relationResolver;
    private ShortCodeHelperInterface $shortCodeHelper;

    public function __construct(
        UrlValidatorInterface $urlValidator,
        EntityManagerInterface $em,
        ShortUrlRelationResolverInterface $relationResolver,
        ShortCodeHelperInterface $shortCodeHelper
    ) {
        $this->urlValidator = $urlValidator;
        $this->em = $em;
        $this->relationResolver = $relationResolver;
        $this->shortCodeHelper = $shortCodeHelper;
    }

    /**
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws Throwable
     */
    public function shorten(ShortUrlMeta $meta): ShortUrl
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        $this->urlValidator->validateUrl($meta->getLongUrl(), $meta->doValidateUrl());

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
            $domainAuthority = $domain !== null ? $domain->getAuthority() : null;

            throw NonUniqueSlugException::fromSlug($shortUrlToBeCreated->getShortCode(), $domainAuthority);
        }
    }
}
