<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Throwable;

use function html_entity_decode;
use function mb_convert_encoding;
use function preg_match;
use function str_contains;
use function str_starts_with;
use function strtolower;
use function trim;

readonly class ShortUrlTitleResolutionHelper implements ShortUrlTitleResolutionHelperInterface
{
    public const int MAX_REDIRECTS = 15;
    public const string CHROME_USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/121.0.0.0 Safari/537.36';

    // Matches the value inside a html title tag
    private const string TITLE_TAG_VALUE = '/<title[^>]*>(.*?)<\/title>/i';
    // Matches the charset inside a Content-Type header
    private const string CHARSET_VALUE = '/charset=([^;]+)/i';

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

        $title = $this->tryToResolveTitle($response, $contentType);
        return $title !== null ? $data->withResolvedTitle($title) : $data;
    }

    private function fetchUrl(string $url): ResponseInterface|null
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

    private function tryToResolveTitle(ResponseInterface $response, string $contentType): string|null
    {
        $collectedBody = '';
        $body = $response->getBody();
        // With streaming enabled, we can walk the body until the </title> tag is found, and then stop
        while (! str_contains($collectedBody, '</title>') && ! $body->eof()) {
            $collectedBody .= $body->read(1024);
        }

        // Try to match the title from the <title /> tag
        preg_match(self::TITLE_TAG_VALUE, $collectedBody, $titleMatches);
        if (! isset($titleMatches[1])) {
            return null;
        }

        // Get the page's charset from Content-Type header
        preg_match(self::CHARSET_VALUE, $contentType, $charsetMatches);

        $title = isset($charsetMatches[1])
            ? mb_convert_encoding($titleMatches[1], 'utf8', $charsetMatches[1])
            : $titleMatches[1];
        return html_entity_decode(trim($title));
    }
}
