<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;
use Zend\Diactoros\Uri;

use function Functional\contains;
use function idn_to_ascii;

use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;

class UrlValidator implements UrlValidatorInterface, RequestMethodInterface, StatusCodeInterface
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
        $this->doValidateUrl($url);
    }

    /**
     * @throws InvalidUrlException
     */
    private function doValidateUrl(string $url, int $redirectNum = 1): void
    {
        // FIXME Guzzle is about to add support for this https://github.com/guzzle/guzzle/pull/2286
        //       Remove custom implementation and manual redirect handling when Guzzle's PR is merged
        $uri = new Uri($url);
        $originalHost = $uri->getHost();
        $normalizedHost = idn_to_ascii($originalHost, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($originalHost !== $normalizedHost) {
            $uri = $uri->withHost($normalizedHost);
        }

        try {
            $resp = $this->httpClient->request(self::METHOD_GET, (string) $uri, [
//                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
                RequestOptions::ALLOW_REDIRECTS => false,
            ]);

            if ($redirectNum < self::MAX_REDIRECTS && $this->statusIsRedirect($resp->getStatusCode())) {
                $this->doValidateUrl($resp->getHeaderLine('Location'), $redirectNum + 1);
            }
        } catch (GuzzleException $e) {
            throw InvalidUrlException::fromUrl($url, $e);
        }
    }

    private function statusIsRedirect(int $statusCode): bool
    {
        return contains([self::STATUS_MOVED_PERMANENTLY, self::STATUS_FOUND], $statusCode);
    }
}
