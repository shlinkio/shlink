<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Throwable;

use function html_entity_decode;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

use const Shlinkio\Shlink\TITLE_TAG_VALUE;

/** @deprecated */
class UrlValidator implements UrlValidatorInterface, RequestMethodInterface
{
    private const MAX_REDIRECTS = 15;
    private const CHROME_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/108.0.0.0 Safari/537.36';

    public function __construct(private ClientInterface $httpClient, private UrlShortenerOptions $options)
    {
    }

    /**
     * @deprecated
     * @throws InvalidUrlException
     */
    public function validateUrl(string $url, bool $doValidate): void
    {
        if (! $doValidate) {
            return;
        }

        $this->validateUrlAndGetResponse($url);
    }

    /**
     * @deprecated
     * @throws InvalidUrlException
     */
    public function validateUrlWithTitle(string $url, bool $doValidate): ?string
    {
        if (! $doValidate && ! $this->options->autoResolveTitles) {
            return null;
        }

        if (! $this->options->autoResolveTitles) {
            $this->validateUrlAndGetResponse($url, self::METHOD_HEAD);
            return null;
        }

        $response = $doValidate ? $this->validateUrlAndGetResponse($url) : $this->getResponse($url);
        if ($response === null) {
            return null;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if (! str_starts_with($contentType, 'text/html')) {
            return null;
        }

        $collectedBody = '';
        $body = $response->getBody();
        // With streaming enabled, we can walk the body until the </title> tag is found, and then stop
        while (! str_contains($collectedBody, '</title>') && ! $body->eof()) {
            $collectedBody .= $body->read(1024);
        }
        preg_match(TITLE_TAG_VALUE, $collectedBody, $matches);
        return isset($matches[1]) ? $this->normalizeTitle($matches[1]) : null;
    }

    /**
     * @param self::METHOD_GET|self::METHOD_HEAD $method
     * @throws InvalidUrlException
     */
    private function validateUrlAndGetResponse(string $url, string $method = self::METHOD_GET): ResponseInterface
    {
        try {
            return $this->httpClient->request($method, $url, [
                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
                RequestOptions::IDN_CONVERSION => true,
                // Making the request with a browser's user agent makes the validation closer to a real user
                RequestOptions::HEADERS => ['User-Agent' => self::CHROME_USER_AGENT],
                RequestOptions::STREAM => true, // This ensures large files are not fully downloaded if not needed
            ]);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }

    private function getResponse(string $url): ?ResponseInterface
    {
        try {
            return $this->validateUrlAndGetResponse($url);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizeTitle(string $title): string
    {
        return html_entity_decode(trim($title));
    }
}
