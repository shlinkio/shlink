<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\DomainResolverInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepositoryInterface;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortCodeHelperInterface;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;
use Throwable;

class UrlShortener implements UrlShortenerInterface
{
    use TagManagerTrait;

    private EntityManagerInterface $em;
    private UrlValidatorInterface $urlValidator;
    private DomainResolverInterface $domainResolver;
    private ShortCodeHelperInterface $shortCodeHelper;

    public function __construct(
        UrlValidatorInterface $urlValidator,
        EntityManagerInterface $em,
        DomainResolverInterface $domainResolver,
        ShortCodeHelperInterface $shortCodeHelper
    ) {
        $this->urlValidator = $urlValidator;
        $this->em = $em;
        $this->domainResolver = $domainResolver;
        $this->shortCodeHelper = $shortCodeHelper;
    }

    /**
     * @param string[] $tags
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws Throwable
     */
    public function shorten(string $url, array $tags, ShortUrlMeta $meta): ShortUrl
    {
        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($url, $tags, $meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        $this->urlValidator->validateUrl($url, $meta->doValidateUrl());

        return $this->em->transactional(function () use ($url, $tags, $meta) {
            $shortUrl = new ShortUrl($url, $meta, $this->domainResolver);
            $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));

            $this->verifyShortCodeUniqueness($meta, $shortUrl);
            $this->em->persist($shortUrl);

            return $shortUrl;
        });
    }

    private function findExistingShortUrlIfExists(string $url, array $tags, ShortUrlMeta $meta): ?ShortUrl
    {
        if (! $meta->findIfExists()) {
            return null;
        }

        /** @var ShortUrlRepositoryInterface $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        return $repo->findOneMatching($url, $tags, $meta);
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
