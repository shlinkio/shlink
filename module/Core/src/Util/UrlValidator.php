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

use function preg_match;
use function trim;

use const Shlinkio\Shlink\TITLE_TAG_VALUE;

class UrlValidator implements UrlValidatorInterface, RequestMethodInterface
{
    private const MAX_REDIRECTS = 15;
    private const CHROME_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/51.0.2704.103 Safari/537.36';

    public function __construct(private ClientInterface $httpClient, private UrlShortenerOptions $options)
    {
    }

    /**
     * @throws InvalidUrlException
     */
    public function validateUrl(string $url, bool $doValidate): void
    {
        if (! $doValidate) {
            return;
        }

        $this->validateUrlAndGetResponse($url);
    }

    public function validateUrlWithTitle(string $url, bool $doValidate): ?string
    {
        if (! $doValidate && ! $this->options->autoResolveTitles()) {
            return null;
        }

        if (! $this->options->autoResolveTitles()) {
            $this->validateUrlAndGetResponse($url, self::METHOD_HEAD);
            return null;
        }

        $response = $doValidate ? $this->validateUrlAndGetResponse($url) : $this->getResponse($url);
        if ($response === null) {
            return null;
        }

        $body = $response->getBody()->__toString();
        preg_match(TITLE_TAG_VALUE, $body, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
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
}
