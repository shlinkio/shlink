<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use League\Uri\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

use function Functional\compose;
use function str_replace;

class NotFoundRedirectResolver implements NotFoundRedirectResolverInterface
{
    private const DOMAIN_PLACEHOLDER = '{DOMAIN}';
    private const ORIGINAL_PATH_PLACEHOLDER = '{ORIGINAL_PATH}';

    public function __construct(private RedirectResponseHelperInterface $redirectResponseHelper)
    {
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
        $redirectUri = Uri::createFromString($redirectUrl);

        $replacePlaceholders = static fn (callable $modifier) => compose(
            static fn (?string $value) =>
                $value === null ? null : str_replace(self::DOMAIN_PLACEHOLDER, $modifier($domain), $value),
            static fn (?string $value) =>
                $value === null ? null : str_replace(self::ORIGINAL_PATH_PLACEHOLDER, $modifier($path), $value),
        );
        $replacePlaceholdersInPath = $replacePlaceholders('\Functional\id');
        $replacePlaceholdersInQuery = $replacePlaceholders('\urlencode');

        return $redirectUri
            ->withPath($replacePlaceholdersInPath($redirectUri->getPath()))
            ->withQuery($replacePlaceholdersInQuery($redirectUri->getQuery()))
            ->__toString();
    }
}
