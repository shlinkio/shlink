<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config;

interface NotFoundRedirectConfigInterface
{
    public function invalidShortUrlRedirect(): string|null;

    public function hasInvalidShortUrlRedirect(): bool;

    public function regular404Redirect(): string|null;

    public function hasRegular404Redirect(): bool;

    public function baseUrlRedirect(): string|null;

    public function hasBaseUrlRedirect(): bool;

    public function expiredShortUrlRedirect(): string|null;

    public function hasExpiredShortUrlRedirect(): bool;
}
