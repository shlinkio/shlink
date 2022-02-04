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

        $this->validateUrlAndGetResponse($url, true);
    }

    public function validateUrlWithTitle(string $url, bool $doValidate): ?string
    {
        if (! $doValidate && ! $this->options->autoResolveTitles()) {
            return null;
        }

        $response = $this->validateUrlAndGetResponse($url, $doValidate);
        if ($response === null || ! $this->options->autoResolveTitles()) {
            return null;
        }

        $body = $response->getBody()->__toString();
        preg_match(TITLE_TAG_VALUE, $body, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    private function validateUrlAndGetResponse(string $url, bool $throwOnError): ?ResponseInterface
    {
        try {
            return $this->httpClient->request(self::METHOD_GET, $url, [
                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
                RequestOptions::IDN_CONVERSION => true,
                // Making the request with a browser's user agent makes the validation closer to a real user
                RequestOptions::HEADERS => ['User-Agent' => self::CHROME_USER_AGENT],
            ]);
        } catch (GuzzleException $e) {
            if ($throwOnError) {
                throw InvalidUrlException::fromUrl($url, $e);
            }

            return null;
        }
    }
}
