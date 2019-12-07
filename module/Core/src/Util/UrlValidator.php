<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Shlinkio\Shlink\Core\Exception\InvalidUrlException;

use function Functional\contains;

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
        // TODO Guzzle does not properly handle IDNs on redirects, just on first request.
        //      Because of that, we have to handle redirects manually.
        try {
            $resp = $this->httpClient->request(self::METHOD_GET, $url, [
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
