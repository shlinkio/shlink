<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

final class EmptyNotFoundRedirectConfig implements NotFoundRedirectConfigInterface
{
    public function invalidShortUrlRedirect(): ?string
    {
        return null;
    }

    public function hasInvalidShortUrlRedirect(): bool
    {
        return false;
    }

    public function regular404Redirect(): ?string
    {
        return null;
    }

    public function hasRegular404Redirect(): bool
    {
        return false;
    }

    public function baseUrlRedirect(): ?string
    {
        return null;
    }

    public function hasBaseUrlRedirect(): bool
    {
        return false;
    }
}
