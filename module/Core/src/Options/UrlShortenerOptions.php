<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use function Functional\contains;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

class UrlShortenerOptions extends AbstractOptions
{
    protected $__strictMode__ = false; // phpcs:ignore

    private bool $validateUrl = true;
    private int $redirectStatusCode = DEFAULT_REDIRECT_STATUS_CODE;
    private int $redirectCacheLifetime = DEFAULT_REDIRECT_CACHE_LIFETIME;
    private bool $autoResolveTitles = false;
    private bool $appendExtraPath = false;

    public function isUrlValidationEnabled(): bool
    {
        return $this->validateUrl;
    }

    protected function setValidateUrl(bool $validateUrl): void
    {
        $this->validateUrl = $validateUrl;
    }

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

    public function autoResolveTitles(): bool
    {
        return $this->autoResolveTitles;
    }

    protected function setAutoResolveTitles(bool $autoResolveTitles): void
    {
        $this->autoResolveTitles = $autoResolveTitles;
    }

    public function appendExtraPath(): bool
    {
        return $this->appendExtraPath;
    }

    protected function setAppendExtraPath(bool $appendExtraPath): void
    {
        $this->appendExtraPath = $appendExtraPath;
    }

    /** @deprecated  */
    protected function setAnonymizeRemoteAddr(bool $anonymizeRemoteAddr): void
    {
        // Keep just for backwards compatibility during hydration
    }

    /** @deprecated  */
    protected function setTrackOrphanVisits(bool $trackOrphanVisits): void
    {
        // Keep just for backwards compatibility during hydration
    }
}
