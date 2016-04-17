<?php
namespace Acelaya\UrlShortener\Service;

use Acelaya\UrlShortener\Entity\ShortUrl;
use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Exception\RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\UriInterface;

class UrlShortener implements UrlShortenerInterface
{
    const DEFAULT_CHARS = '123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ';

    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var string
     */
    private $chars;

    public function __construct(
        ClientInterface $httpClient,
        EntityManagerInterface $em,
        $chars = self::DEFAULT_CHARS
    ) {
        $this->httpClient = $httpClient;
        $this->em = $em;
        $this->chars = $chars;
    }

    /**
     * @param UriInterface $url
     * @return string
     * @throws InvalidUrlException
     * @throws RuntimeException
     */
    public function urlToShortCode(UriInterface $url)
    {
        // If the url already exists in the database, just return its short code
        $shortUrl = $this->em->getRepository(ShortUrl::class)->findOneBy([
            'originalUrl' => $url
        ]);
        if (isset($shortUrl)) {
            return $shortUrl->getShortCode();
        }

        // Check that the URL exists
        $this->checkUrlExists($url);

        // Transactionally insert the short url, then generate the short code and finally update the short code
        try {
            $this->em->beginTransaction();

            // First, create the short URL with an empty short code
            $shortUrl = new ShortUrl();
            $shortUrl->setOriginalUrl($url);
            $this->em->persist($shortUrl);
            $this->em->flush();

            // Generate the short code and persist it
            $shortCode = $this->convertAutoincrementIdToShortCode($shortUrl->getId());
            $shortUrl->setShortCode($shortCode);
            $this->em->flush();

            $this->em->commit();
            return $shortCode;
        } catch (ORMException $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
                $this->em->close();
            }

            throw new RuntimeException('An error occured while persisting the short URL', -1, $e);
        }
    }

    /**
     * Tries to perform a GET request to provided url, returning true on success and false on failure
     *
     * @param UriInterface $url
     * @return bool
     */
    protected function checkUrlExists(UriInterface $url)
    {
        try {
            $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }

    /**
     * Generates the unique shortcode for an autoincrement ID
     *
     * @param int $id
     * @return string
     */
    protected function convertAutoincrementIdToShortCode($id)
    {
        $id = intval($id);
        $length = strlen($this->chars);
        $code = '';

        while ($id > $length - 1) {
            // Determine the value of the next higher character in the short code and prepend it
            $code = $this->chars[fmod($id, $length)] . $code;
            $id = floor($id / $length);
        }

        return $this->chars[$id] . $code;
    }

    /**
     * @param string $shortCode
     * @return string
     */
    public function shortCodeToUrl($shortCode)
    {
        // Validate short code format
        
    }
}
