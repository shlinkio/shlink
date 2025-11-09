<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use JsonSerializable;

final readonly class NotFoundRedirects implements JsonSerializable
{
    private function __construct(
        public string|null $baseUrlRedirect,
        public string|null $regular404Redirect,
        public string|null $invalidShortUrlRedirect,
        public string|null $expiredShortUrlRedirect,
    ) {
    }

    public static function withRedirects(
        string|null $baseUrlRedirect,
        string|null $regular404Redirect = null,
        string|null $invalidShortUrlRedirect = null,
        string|null $expiredShortUrlRedirect = null,
    ): self {
        return new self($baseUrlRedirect, $regular404Redirect, $invalidShortUrlRedirect, $expiredShortUrlRedirect);
    }

    public static function withoutRedirects(): self
    {
        return new self(null, null, null, null);
    }

    public static function fromConfig(NotFoundRedirectConfigInterface $config): self
    {
        return new self(
            $config->baseUrlRedirect,
            $config->regular404Redirect,
            $config->invalidShortUrlRedirect,
            $config->expiredShortUrlRedirect,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'baseUrlRedirect' => $this->baseUrlRedirect,
            'regular404Redirect' => $this->regular404Redirect,
            'invalidShortUrlRedirect' => $this->invalidShortUrlRedirect,
            'expiredShortUrlRedirect' => $this->expiredShortUrlRedirect,
        ];
    }
}
