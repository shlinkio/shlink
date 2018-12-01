<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Service;

use Cake\Chronos\Chronos;
use Cocur\Slugify\SlugifyInterface;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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
use function floor;
use function fmod;
use function preg_match;
use function strlen;

class UrlShortener implements UrlShortenerInterface
{
    use TagManagerTrait;

    /** @deprecated */
    public const DEFAULT_CHARS = UrlShortenerOptions::DEFAULT_CHARS;
    private const ID_INCREMENT = 200000;

    /** @var ClientInterface */
    private $httpClient;
    /** @var EntityManagerInterface */
    private $em;
    /** @var SlugifyInterface */
    private $slugger;
    /** @var UrlShortenerOptions */
    private $options;

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        UrlShortenerOptions $options,
        SlugifyInterface $slugger
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->options = $options;
        $this->slugger = $slugger;
    }

    /**
     * @throws NonUniqueSlugException
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(
        UriInterface $url,
        array $tags = [],
        ?Chronos $validSince = null,
        ?Chronos $validUntil = null,
        ?string $customSlug = null,
        ?int $maxVisits = null
    ): ShortUrl {
        // If the URL validation is enabled, check that the URL actually exists
        if ($this->options->isUrlValidationEnabled()) {
            $this->checkUrlExists($url);
        }
        $customSlug = $this->processCustomSlug($customSlug);

        // Transactionally insert the short url, then generate the short code and finally update the short code
        try {
            $this->em->beginTransaction();

            // First, create the short URL with an empty short code
            $shortUrl = new ShortUrl(
                (string) $url,
                ShortUrlMeta::createFromParams($validSince, $validUntil, null, $maxVisits)
            );
            $this->em->persist($shortUrl);
            $this->em->flush();

            // Generate the short code and persist it
            // TODO Somehow provide the logic to calculate the shortCode to avoid the need of a setter
            $shortCode = $customSlug ?? $this->convertAutoincrementIdToShortCode((float) $shortUrl->getId());
            $shortUrl->setShortCode($shortCode)
                     ->setTags($this->tagNamesToEntities($this->em, $tags));
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

    private function checkUrlExists(UriInterface $url): void
    {
        try {
            $this->httpClient->request('GET', $url, ['allow_redirects' => [
                'max' => 15,
            ]]);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
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

    private function processCustomSlug(?string $customSlug): ?string
    {
        if ($customSlug === null) {
            return null;
        }

        // If a custom slug was provided, make sure it's unique
        $customSlug = $this->slugger->slugify($customSlug);
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy(['shortCode' => $customSlug]);
        if ($shortUrl !== null) {
            throw NonUniqueSlugException::fromSlug($customSlug);
        }

        return $customSlug;
    }

    /**
     * Tries to find the mapped URL for provided short code. Returns null if not found
     *
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
