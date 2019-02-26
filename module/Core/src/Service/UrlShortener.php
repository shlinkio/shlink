<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Doctrine\ORM\EntityManagerInterface;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Exception\NonUniqueSlugException;
use Shlinkio\Shlink\Core\Exception\RuntimeException;
use Shlinkio\Shlink\Core\Model\ShortUrlMeta;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\Repository\ShortUrlRepository;
use Shlinkio\Shlink\Core\Util\TagManagerTrait;
use Throwable;

use function array_reduce;
use function count;
use function floor;
use function fmod;
use function Functional\contains;
use function Functional\invoke;
use function preg_match;
use function strlen;

class UrlShortener implements UrlShortenerInterface
{
    use TagManagerTrait;

    private const ID_INCREMENT = 200000;

    /** @var ClientInterface */
    private $httpClient;
    /** @var EntityManagerInterface */
    private $em;
    /** @var UrlShortenerOptions */
    private $options;

    public function __construct(ClientInterface $httpClient, EntityManagerInterface $em, UrlShortenerOptions $options)
    {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->options = $options;
    }

    /**
     * @param string[] $tags
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(UriInterface $url, array $tags, ShortUrlMeta $meta): ShortUrl
    {
        $url = (string) $url;

        // First, check if a short URL exists for all provided params
        $existingShortUrl = $this->findExistingShortUrlIfExists($url, $tags, $meta);
        if ($existingShortUrl !== null) {
            return $existingShortUrl;
        }

        // If the URL validation is enabled, check that the URL actually exists
        if ($this->options->isUrlValidationEnabled()) {
            $this->checkUrlExists($url);
        }
        $this->verifyCustomSlug($meta);

        // Transactionally insert the short url, then generate the short code and finally update the short code
        try {
            $this->em->beginTransaction();

            // First, create the short URL with an empty short code
            $shortUrl = new ShortUrl($url, $meta);
            $this->em->persist($shortUrl);
            $this->em->flush();

            // Generate the short code and persist it if no custom slug was provided
            if (! $meta->hasCustomSlug()) {
                // TODO Somehow provide the logic to calculate the shortCode to avoid the need of a setter
                $shortCode = $this->convertAutoincrementIdToShortCode((float) $shortUrl->getId());
                $shortUrl->setShortCode($shortCode);
            }
            $shortUrl->setTags($this->tagNamesToEntities($this->em, $tags));
            $this->em->flush();

            $this->em->commit();
            return $shortUrl;
        } catch (Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
                $this->em->close();
            }

            throw new RuntimeException('An error occurred while persisting the short URL', -1, $e);
        }
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
        /** @var ShortUrl|null $shortUrl */
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy($criteria);
        if ($shortUrl === null) {
            return null;
        }

        if ($meta->hasMaxVisits() && $meta->getMaxVisits() !== $shortUrl->getMaxVisits()) {
            return null;
        }
        if ($meta->hasValidSince() && ! $meta->getValidSince()->eq($shortUrl->getValidSince())) {
            return null;
        }
        if ($meta->hasValidUntil() && ! $meta->getValidUntil()->eq($shortUrl->getValidUntil())) {
            return null;
        }

        $shortUrlTags = invoke($shortUrl->getTags(), '__toString');
        $hasAllTags = count($shortUrlTags) === count($tags) && array_reduce(
            $tags,
            function (bool $hasAllTags, string $tag) use ($shortUrlTags) {
                return $hasAllTags && contains($shortUrlTags, $tag);
            },
            true
        );

        return $hasAllTags ? $shortUrl : null;
    }

    private function checkUrlExists(string $url): void
    {
        try {
            $this->httpClient->request(RequestMethodInterface::METHOD_GET, $url, [
                RequestOptions::ALLOW_REDIRECTS => ['max' => 15],
            ]);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }

    private function verifyCustomSlug(ShortUrlMeta $meta): void
    {
        if (! $meta->hasCustomSlug()) {
            return;
        }

        $customSlug = $meta->getCustomSlug();

        /** @var ShortUrlRepository $repo */
        $repo = $this->em->getRepository(ShortUrl::class);
        $shortUrlsCount = $repo->count(['shortCode' => $customSlug]);
        if ($shortUrlsCount > 0) {
            throw NonUniqueSlugException::fromSlug($customSlug);
        }
    }

    private function convertAutoincrementIdToShortCode(float $id): string
    {
        $id += self::ID_INCREMENT; // Increment the Id so that the generated shortcode is not too short
        $chars = $this->options->getChars();

        $length = strlen($chars);
        $code = '';

        while ($id > 0) {
            // Determine the value of the next higher character in the short code and prepend it
            $code = $chars[(int) fmod($id, $length)] . $code;
            $id = floor($id / $length);
        }

        return $chars[(int) $id] . $code;
    }

    /**
     * @throws InvalidShortCodeException
     * @throws EntityDoesNotExistException
     */
    public function shortCodeToUrl(string $shortCode): ShortUrl
    {
        $chars = $this->options->getChars();

        // Validate short code format
        if (! preg_match('|[' . $chars . ']+|', $shortCode)) {
            throw InvalidShortCodeException::fromCharset($shortCode, $chars);
        }

        /** @var ShortUrlRepository $shortUrlRepo */
        $shortUrlRepo = $this->em->getRepository(ShortUrl::class);
        $shortUrl = $shortUrlRepo->findOneByShortCode($shortCode);
        if ($shortUrl === null) {
            throw EntityDoesNotExistException::createFromEntityAndConditions(ShortUrl::class, [
                'shortCode' => $shortCode,
            ]);
        }

        return $shortUrl;
    }
}
