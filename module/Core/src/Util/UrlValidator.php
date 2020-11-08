<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;

class UrlValidator implements UrlValidatorInterface, RequestMethodInterface
{
    private const MAX_REDIRECTS = 15;

    private ClientInterface $httpClient;
    private UrlShortenerOptions $options;

    public function __construct(ClientInterface $httpClient, UrlShortenerOptions $options)
    {
        $this->httpClient = $httpClient;
        $this->options = $options;
    }

    /**
     * @throws InvalidUrlException
     */
    public function validateUrl(string $url, ?bool $doValidate): void
    {
        // If the URL validation is not enabled or it was explicitly set to not validate, skip check
        $doValidate = $doValidate ?? $this->options->isUrlValidationEnabled();
        if (! $doValidate) {
            return;
        }

        try {
            $this->httpClient->request(self::METHOD_GET, $url, [
                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
                RequestOptions::IDN_CONVERSION => true,
            ]);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }
}
