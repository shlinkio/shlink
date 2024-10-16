<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;

final readonly class NotFoundRedirectOptions implements NotFoundRedirectConfigInterface
{
    public function __construct(
        public ?string $invalidShortUrl = null,
        public ?string $regular404 = null,
        public ?string $baseUrl = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            invalidShortUrl: EnvVars::DEFAULT_INVALID_SHORT_URL_REDIRECT->loadFromEnv(),
            regular404: EnvVars::DEFAULT_REGULAR_404_REDIRECT->loadFromEnv(),
            baseUrl: EnvVars::DEFAULT_BASE_URL_REDIRECT->loadFromEnv(),
        );
    }

    public function invalidShortUrlRedirect(): ?string
    {
        return $this->invalidShortUrl;
    }

    public function hasInvalidShortUrlRedirect(): bool
    {
        return $this->invalidShortUrl !== null;
    }

    public function regular404Redirect(): ?string
    {
        return $this->regular404;
    }

    public function hasRegular404Redirect(): bool
    {
        return $this->regular404 !== null;
    }

    public function baseUrlRedirect(): ?string
    {
        return $this->baseUrl;
    }

    public function hasBaseUrlRedirect(): bool
    {
        return $this->baseUrl !== null;
    }
}
