<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;

final class NotFoundRedirectOptions implements NotFoundRedirectConfigInterface
{
    public function __construct(
        public readonly ?string $invalidShortUrl = null,
        public readonly ?string $regular404 = null,
        public readonly ?string $baseUrl = null,
    ) {
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
