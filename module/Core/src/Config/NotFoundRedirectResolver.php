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

use function str_replace;
use function urlencode;

class NotFoundRedirectResolver implements NotFoundRedirectResolverInterface
{
    private const DOMAIN_PLACEHOLDER = '{DOMAIN}';
    private const ORIGINAL_PATH_PLACEHOLDER = '{ORIGINAL_PATH}';

    public function __construct(
        private readonly RedirectResponseHelperInterface $redirectResponseHelper,
        private readonly LoggerInterface $logger,
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
        try {
            $redirectUri = Uri::createFromString($redirectUrl);
        } catch (SyntaxError $e) {
            $this->logger->warning('It was not possible to parse "{url}" as a valid URL: {e}', [
                'e' => $e,
                'url' => $redirectUrl,
            ]);
            return $redirectUrl;
        }

        $path = $currentUri->getPath();
        $domain = $currentUri->getAuthority();

        $replacePlaceholderForPattern = static fn (string $pattern, string $replace, ?string $value): string|null =>
            $value === null ? null : str_replace($pattern, $replace, $value);

        $replacePlaceholders = static function (
            callable $modifier,
            ?string $value,
        ) use (
            $replacePlaceholderForPattern,
            $path,
            $domain,
        ): string|null {
            $value = $replacePlaceholderForPattern($modifier(self::DOMAIN_PLACEHOLDER), $modifier($domain), $value);
            return $replacePlaceholderForPattern($modifier(self::ORIGINAL_PATH_PLACEHOLDER), $modifier($path), $value);
        };

        $replacePlaceholdersInPath = static function (string $path) use ($replacePlaceholders): string {
            $result = $replacePlaceholders(static fn (mixed $v) => $v, $path);
            return str_replace('//', '/', $result ?? '');
        };
        $replacePlaceholdersInQuery = static fn (?string $query): string|null => $replacePlaceholders(
            urlencode(...),
            $query,
        );

        return $redirectUri
            ->withPath($replacePlaceholdersInPath($redirectUri->getPath()))
            ->withQuery($replacePlaceholdersInQuery($redirectUri->getQuery()))
            ->__toString();
    }
}
