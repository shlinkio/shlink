<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use function Functional\contains;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

class RedirectOptions extends AbstractOptions
{
    private int $redirectStatusCode = DEFAULT_REDIRECT_STATUS_CODE;
    private int $redirectCacheLifetime = DEFAULT_REDIRECT_CACHE_LIFETIME;

    public function redirectStatusCode(): int
    {
        return $this->redirectStatusCode;
    }

    protected function setRedirectStatusCode(int $redirectStatusCode): void
    {
        $this->redirectStatusCode = $this->normalizeRedirectStatusCode($redirectStatusCode);
    }

    private function normalizeRedirectStatusCode(int $statusCode): int
    {
        return contains([301, 302], $statusCode) ? $statusCode : DEFAULT_REDIRECT_STATUS_CODE;
    }

    public function redirectCacheLifetime(): int
    {
        return $this->redirectCacheLifetime;
    }

    protected function setRedirectCacheLifetime(int $redirectCacheLifetime): void
    {
        $this->redirectCacheLifetime = $redirectCacheLifetime > 0
            ? $redirectCacheLifetime
            : DEFAULT_REDIRECT_CACHE_LIFETIME;
    }
}
