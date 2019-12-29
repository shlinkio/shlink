<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;

class UrlValidator implements UrlValidatorInterface, RequestMethodInterface
{
    private const MAX_REDIRECTS = 15;

    /** @var ClientInterface */
    private $httpClient;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @throws InvalidUrlException
     */
    public function validateUrl(string $url): void
    {
        try {
            $this->httpClient->request(self::METHOD_GET, $url, [
                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
            ]);
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }
}
