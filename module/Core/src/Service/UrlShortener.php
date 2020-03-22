<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Domain\Resolver\PersistenceDomainResolver;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Shlinkio\Shlink\Core\Util\UrlValidatorInterface;
use Throwable;

use function array_reduce;

class UrlShortener implements UrlShortenerInterface
{
    use TagManagerTrait;

    private EntityManagerInterface $em;
    private UrlValidatorInterface $urlValidator;

    public function __construct(
        UrlValidatorInterface $urlValidator,
        EntityManagerInterface $em
    ) {
        $this->urlValidator = $urlValidator;
        $this->em = $em;
    }

    /**
     * @param string[] $tags
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws Throwable
     */
    public function urlToShortCode(UriInterface $url, array $tags, ShortUrlMeta $meta): ShortUrl
    {
        $url = (string) $url;

        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($url, $tags, $meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        $this->urlValidator->validateUrl($url);
        $this->em->beginTransaction();
        $shortUrl = new ShortUrl($url, $meta, new PersistenceDomainResolver($this->em));
        $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));

        try {
            $this->verifyShortCodeUniqueness($meta, $shortUrl);
            $this->em->persist($shortUrl);
            $this->em->flush();
            $this->em->commit();
        } catch (Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
                $this->em->close();
            }

            throw $e;
        }

        return $shortUrl;
    }

    private function findExistingShortUrlIfExists(string $url, array $tags, ShortUrlMeta $meta): ?ShortUrl
    {
        if (! $meta->findIfExists()) {
            return null;
        }

        $criteria = ['longUrl' => $url];
        if ($meta->hasCustomSlug()) {
            $criteria['shortCode'] = $meta->getCustomSlug();
        }
        /** @var ShortUrl[] $shortUrls */
        $shortUrls = $this->em->getRepository(ShortUrl::class)->findBy($criteria);
        if (empty($shortUrls)) {
            return null;
        }

        // Iterate short URLs until one that matches is found, or return null otherwise
        return array_reduce($shortUrls, function (?ShortUrl $found, ShortUrl $shortUrl) use ($tags, $meta) {
            if ($found !== null) {
                return $found;
            }

            return $shortUrl->matchesCriteria($meta, $tags) ? $shortUrl : null;
        });
    }

    private function verifyShortCodeUniqueness(ShortUrlMeta $meta, ShortUrl $shortUrlToBeCreated): void
    {
        $shortCode = $shortUrlToBeCreated->getShortCode();
        $domain = $meta->getDomain();

        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $otherShortUrlsExist = $repo->shortCodeIsInUse($shortCode, $domain);

        if ($otherShortUrlsExist && $meta->hasCustomSlug()) {
            throw NonUniqueSlugException::fromSlug($shortCode, $domain);
        }

        if ($otherShortUrlsExist) {
            $shortUrlToBeCreated->regenerateShortCode();
            $this->verifyShortCodeUniqueness($meta, $shortUrlToBeCreated);
        }
    }
}
