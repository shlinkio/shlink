<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use Laminas\Diactoros\Exception\InvalidArgumentException;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function str_replace;
use function urlencode;

class NotFoundRedirectResolver implements NotFoundRedirectResolverInterface
{
    private const string DOMAIN_PLACEHOLDER = '{DOMAIN}';
    private const string ORIGINAL_PATH_PLACEHOLDER = '{ORIGINAL_PATH}';

    public function __construct(
        private readonly RedirectResponseHelperInterface $redirectResponseHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function resolveRedirectResponse(
        NotFoundType $notFoundType,
        NotFoundRedirectConfigInterface $config,
        UriInterface $currentUri,
    ): ResponseInterface|null {
        $urlToRedirectTo = match (true) {
            $notFoundType->isBaseUrl() && $config->hasBaseUrlRedirect() => $config->baseUrlRedirect(),
            $notFoundType->isRegularNotFound() && $config->hasRegular404Redirect() => $config->regular404Redirect(),
            $notFoundType->isInvalidShortUrl() && $config->hasInvalidShortUrlRedirect() =>
                $config->invalidShortUrlRedirect(),
            $notFoundType->isExpiredShortUrl() && $config->hasExpiredShortUrlRedirect() =>
                $config->expiredShortUrlRedirect(),
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
            $redirectUri = new Uri($redirectUrl);
        } catch (InvalidArgumentException $e) {
            $this->logger->warning('It was not possible to parse "{url}" as a valid URL: {e}', [
                'e' => $e,
                'url' => $redirectUrl,
            ]);
            return $redirectUrl;
        }

        $path = $currentUri->getPath();
        $domain = $currentUri->getAuthority();

        $replacePlaceholders = static function (
            callable $modifier,
            string $value,
        ) use (
            $path,
            $domain,
        ): string {
            $value = str_replace(urlencode(self::DOMAIN_PLACEHOLDER), $modifier($domain), $value);
            return str_replace(urlencode(self::ORIGINAL_PATH_PLACEHOLDER), $modifier($path), $value);
        };

        $replacePlaceholdersInPath = static function (string $path) use ($replacePlaceholders): string {
            $result = $replacePlaceholders(static fn (mixed $v) => $v, $path);
            return str_replace('//', '/', $result);
        };
        $replacePlaceholdersInQuery = static fn (string $query): string => $replacePlaceholders(
            urlencode(...),
            $query,
        );

        return $redirectUri
            ->withPath($replacePlaceholdersInPath($redirectUri->getPath()))
            ->withQuery($replacePlaceholdersInQuery($redirectUri->getQuery()))
            ->__toString();
    }
}
