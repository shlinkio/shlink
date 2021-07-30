<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

use JsonSerializable;

final class NotFoundRedirects implements JsonSerializable
{
    public function __construct(
        private ?string $baseUrlRedirect = null,
        private ?string $regular404Redirect = null,
        private ?string $invalidShortUrlRedirect = null,
    ) {
    }

    public function baseUrlRedirect(): ?string
    {
        return $this->baseUrlRedirect;
    }

    public function regular404Redirect(): ?string
    {
        return $this->regular404Redirect;
    }

    public function invalidShortUrlRedirect(): ?string
    {
        return $this->invalidShortUrlRedirect;
    }

    public function jsonSerialize(): array
    {
        return [
            'baseUrlRedirect' => $this->baseUrlRedirect,
            'regular404Redirect' => $this->regular404Redirect,
            'invalidShortUrlRedirect' => $this->invalidShortUrlRedirect,
        ];
    }
}
