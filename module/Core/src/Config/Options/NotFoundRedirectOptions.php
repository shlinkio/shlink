<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;

final readonly class NotFoundRedirectOptions implements NotFoundRedirectConfigInterface
{
    public function __construct(
        public string|null $invalidShortUrl = null,
        public string|null $regular404 = null,
        public string|null $baseUrl = null,
        public string|null $expiredShortUrl = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            invalidShortUrl: EnvVars::DEFAULT_INVALID_SHORT_URL_REDIRECT->loadFromEnv(),
            regular404: EnvVars::DEFAULT_REGULAR_404_REDIRECT->loadFromEnv(),
            baseUrl: EnvVars::DEFAULT_BASE_URL_REDIRECT->loadFromEnv(),
            expiredShortUrl: EnvVars::DEFAULT_EXPIRED_SHORT_URL_REDIRECT->loadFromEnv(),
        );
    }

    public function invalidShortUrlRedirect(): string|null
    {
        return $this->invalidShortUrl;
    }

    public function hasInvalidShortUrlRedirect(): bool
    {
        return $this->invalidShortUrl !== null;
    }

    public function regular404Redirect(): string|null
    {
        return $this->regular404;
    }

    public function hasRegular404Redirect(): bool
    {
        return $this->regular404 !== null;
    }

    public function baseUrlRedirect(): string|null
    {
        return $this->baseUrl;
    }

    public function hasBaseUrlRedirect(): bool
    {
        return $this->baseUrl !== null;
    }

    public function expiredShortUrlRedirect(): string|null
    {
        return $this->expiredShortUrl;
    }

    public function hasExpiredShortUrlRedirect(): bool
    {
        return $this->expiredShortUrl !== null;
    }
}
