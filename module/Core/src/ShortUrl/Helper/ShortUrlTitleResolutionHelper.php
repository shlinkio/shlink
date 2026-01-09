<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Helper;

use Closure;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Laminas\Stdlib\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Throwable;

use function function_exists;
use function html_entity_decode;
use function iconv;
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

    /** Matches the value inside a html title tag */
    private const string TITLE_TAG_VALUE = '/<title[^>]*>(.*?)<\/title>/i';
    /** Matches the charset inside a Content-Type header */
    private const string CHARSET_VALUE = '/charset=([^;]+)/i';
    /** Matches the charset from charset-related <meta /> tags */
    private const string CHARSET_FROM_META = '/<meta\b[^>]*\bcharset\s*=\s*(?:["\']?)([^"\'\s>;]+)/i';

    /**
     * @param (Closure(): bool)|null $isIconvInstalled
     */
    public function __construct(
        private ClientInterface $httpClient,
        private UrlShortenerOptions $options,
        private LoggerInterface $logger,
        private Closure|null $isIconvInstalled = null,
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
        return $title !== null ? $data->withResolvedTitle(html_entity_decode(trim($title))) : $data;
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
                // This ensures large files are not fully downloaded if not needed
                RequestOptions::STREAM => true,
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
        $titleInOriginalEncoding = $titleMatches[1] ?? null;
        if ($titleInOriginalEncoding === null) {
            return null;
        }
        ;
        $pageCharset = $this->resolvePageCharset($contentType, $body, $collectedBody);
        if ($pageCharset === null) {
            // If it was not possible to determine the page's charset, ignore the title to avoid the risk of encoding
            // errors when the value is persisted
            return null;
        }

        return $this->encodeToUtf8WithMbString($titleInOriginalEncoding, $pageCharset)
            ?? $this->encodeToUtf8WithIconv($titleInOriginalEncoding, $pageCharset)
            ?? $titleInOriginalEncoding;
    }

    /**
     * Tries to resolve the page's charset by looking into the:
     * 1. Content-Type header
     * 2. <meta charset="???"> tag
     * 3. <meta http-equiv="Content-Type" content="text/html; charset=???"> tag
     *
     * @param StreamInterface $body - The body stream, in case we need to continue reading from it
     * @param string $collectedBody - The part of the body that has already been read while looking for the title
     */
    private function resolvePageCharset(string $contentType, StreamInterface $body, string $collectedBody): string|null
    {
        // First try to resolve the charset from the `Content-Type` header
        preg_match(self::CHARSET_VALUE, $contentType, $charsetMatches);
        $pageCharset = $charsetMatches[1] ?? null;
        if ($pageCharset !== null) {
            return $pageCharset;
        }

        $readCharsetFromMeta = static function (string $collectedBody): string|null {
            preg_match(self::CHARSET_FROM_META, $collectedBody, $charsetFromMetaMatches);
            return $charsetFromMetaMatches[1] ?? null;
        };

        // Continue reading the body, looking for any of the charset meta tags
        $charsetFromMeta = $readCharsetFromMeta($collectedBody);
        while ($charsetFromMeta === null && ! $body->eof()) {
            $collectedBody .= $body->read(1024);
            $charsetFromMeta = $readCharsetFromMeta($collectedBody);
        }

        return $charsetFromMeta;
    }

    private function encodeToUtf8WithMbString(string $titleInOriginalEncoding, string $pageCharset): string|null
    {
        try {
            return mb_convert_encoding($titleInOriginalEncoding, 'utf-8', $pageCharset) ?: null;
        } catch (Throwable $e) {
            $this->logger->warning('It was impossible to encode page title in UTF-8 with mb_convert_encoding. {e}', [
                'e' => $e,
            ]);
            return null;
        }
    }

    private function encodeToUtf8WithIconv(string $titleInOriginalEncoding, string $pageCharset): string|null
    {
        $isIconvInstalled = ($this->isIconvInstalled ?? fn () => function_exists('iconv'))();
        if (! $isIconvInstalled) {
            $this->logger->warning('Missing iconv extension. Skipping title encoding');
            return null;
        }

        try {
            ErrorHandler::start();
            $title = iconv($pageCharset, 'utf-8', $titleInOriginalEncoding);
            ErrorHandler::stop(throw: true);
            return $title ?: null;
        } catch (Throwable $e) {
            $this->logger->warning('It was impossible to encode page title in UTF-8 with iconv. {e}', ['e' => $e]);
            return null;
        }
    }
}
