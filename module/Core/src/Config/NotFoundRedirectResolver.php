<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function Functional\compose;
use function str_replace;

class NotFoundRedirectResolver implements NotFoundRedirectResolverInterface
{
    private const DOMAIN_PLACEHOLDER = '{DOMAIN}';
    private const ORIGINAL_PATH_PLACEHOLDER = '{ORIGINAL_PATH}';

    public function __construct(
        private RedirectResponseHelperInterface $redirectResponseHelper,
        private LoggerInterface $logger,
    ) {
    }

    public function resolveRedirectResponse(
        NotFoundType $notFoundType,
        NotFoundRedirectConfigInterface $config,
        UriInterface $currentUri,
    ): ?ResponseInterface {
        $urlToRedirectTo = match (true) {
            $notFoundType->isBaseUrl() && $config->hasBaseUrlRedirect() => $config->baseUrlRedirect(),
            $notFoundType->isRegularNotFound() && $config->hasRegular404Redirect() => $config->regular404Redirect(),
            $notFoundType->isInvalidShortUrl() && $config->hasInvalidShortUrlRedirect() =>
                $config->invalidShortUrlRedirect(),
            default => null,
        };

        if ($urlToRedirectTo === null) {
            return null;
        }

        return $this->redirectResponseHelper->buildRedirectResponse(
            $this->resolvePlaceholders($currentUri, $urlToRedirectTo),
        );
    }

    private function resolvePlaceholders(UriInterface $currentUri, string $redirectUrl): string
    {
        $domain = $currentUri->getAuthority();
        $path = $currentUri->getPath();

        try {
            $redirectUri = Uri::createFromString($redirectUrl);
        } catch (SyntaxError $e) {
            $this->logger->warning('It was not possible to parse "{url}" as a valid URL: {e}', [
                'e' => $e,
                'url' => $redirectUrl,
            ]);
            return $redirectUrl;
        }

        $replacePlaceholderForPattern = static fn (string $pattern, string $replace, callable $modifier) =>
            static fn (?string $value) =>
                $value === null ? null : str_replace($modifier($pattern), $modifier($replace), $value);
        $replacePlaceholders = static fn (callable $modifier) => compose(
            $replacePlaceholderForPattern(self::DOMAIN_PLACEHOLDER, $domain, $modifier),
            $replacePlaceholderForPattern(self::ORIGINAL_PATH_PLACEHOLDER, $path, $modifier),
        );
        $replacePlaceholdersInPath = compose(
            $replacePlaceholders('\Functional\id'),
            static fn (?string $path) => $path === null ? null : str_replace('//', '/', $path), // Fix duplicated bars
        );
        $replacePlaceholdersInQuery = $replacePlaceholders('\urlencode');

        return $redirectUri
            ->withPath($replacePlaceholdersInPath($redirectUri->getPath()))
            ->withQuery($replacePlaceholdersInQuery($redirectUri->getQuery()))
            ->__toString();
    }
}
