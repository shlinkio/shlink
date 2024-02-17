<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Throwable;

use function html_entity_decode;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

use const Shlinkio\Shlink\TITLE_TAG_VALUE;

readonly class ShortUrlTitleResolutionHelper implements ShortUrlTitleResolutionHelperInterface
{
    public const MAX_REDIRECTS = 15;
    public const CHROME_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/121.0.0.0 Safari/537.36';

    public function __construct(
        private ClientInterface $httpClient,
        private UrlShortenerOptions $options,
    ) {
    }

    /**
     * @template T of TitleResolutionModelInterface
     * @param T $data
     * @return T
     */
    public function processTitle(TitleResolutionModelInterface $data): TitleResolutionModelInterface
    {
        if (! $this->options->autoResolveTitles || $data->hasTitle()) {
            return $data;
        }

        $response = $this->fetchUrl($data->getLongUrl());
        if ($response === null) {
            return $data;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if (! str_starts_with($contentType, 'text/html')) {
            return $data;
        }

        $title = $this->tryToResolveTitle($response);
        return $title !== null ? $data->withResolvedTitle($title) : $data;
    }

    private function fetchUrl(string $url): ?ResponseInterface
    {
        try {
            return $this->httpClient->request(RequestMethodInterface::METHOD_GET, $url, [
                // Add a sensible 3-second timeout that prevents hanging here forever
                RequestOptions::TIMEOUT => 3,
                RequestOptions::CONNECT_TIMEOUT => 3,
                // Prevent potential infinite redirection loops
                RequestOptions::ALLOW_REDIRECTS => ['max' => self::MAX_REDIRECTS],
                RequestOptions::IDN_CONVERSION => true,
                // Making the request with a browser's user agent results in responses closer to a real user
                RequestOptions::HEADERS => ['User-Agent' => self::CHROME_USER_AGENT],
                RequestOptions::STREAM => true, // This ensures large files are not fully downloaded if not needed
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    private function tryToResolveTitle(ResponseInterface $response): ?string
    {
        $collectedBody = '';
        $body = $response->getBody();
        // With streaming enabled, we can walk the body until the </title> tag is found, and then stop
        while (! str_contains($collectedBody, '</title>') && ! $body->eof()) {
            $collectedBody .= $body->read(1024);
        }
        preg_match(TITLE_TAG_VALUE, $collectedBody, $matches);
        return isset($matches[1]) ? $this->normalizeTitle($matches[1]) : null;
    }

    private function normalizeTitle(string $title): string
    {
        return html_entity_decode(trim($title));
    }
}
