<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use function Functional\contains;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

final class RedirectOptions
{
    public readonly int $redirectStatusCode;
    public readonly int $redirectCacheLifetime;

    public function __construct(
        int $redirectStatusCode = DEFAULT_REDIRECT_STATUS_CODE,
        int $redirectCacheLifetime = DEFAULT_REDIRECT_CACHE_LIFETIME,
    ) {
        $this->redirectStatusCode = contains([301, 302], $redirectStatusCode)
            ? $redirectStatusCode
            : DEFAULT_REDIRECT_STATUS_CODE;
        $this->redirectCacheLifetime = $redirectCacheLifetime > 0
            ? $redirectCacheLifetime
            : DEFAULT_REDIRECT_CACHE_LIFETIME;
    }
}
