<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Util\RedirectStatus;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_VISIBILITY;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

final readonly class RedirectOptions
{
    public RedirectStatus $redirectStatusCode;
    public int $redirectCacheLifetime;
    /** @var 'public'|'private' */
    public string $redirectCacheVisibility;

    public function __construct(
        int $redirectStatusCode = RedirectStatus::STATUS_302->value,
        int $redirectCacheLifetime = DEFAULT_REDIRECT_CACHE_LIFETIME,
        string|null $redirectCacheVisibility = DEFAULT_REDIRECT_CACHE_VISIBILITY,
    ) {
        $this->redirectStatusCode = RedirectStatus::tryFrom($redirectStatusCode) ?? DEFAULT_REDIRECT_STATUS_CODE;
        $this->redirectCacheLifetime = $redirectCacheLifetime > 0
            ? $redirectCacheLifetime
            : DEFAULT_REDIRECT_CACHE_LIFETIME;
        $this->redirectCacheVisibility = $redirectCacheVisibility === 'public' || $redirectCacheVisibility === 'private'
            ? $redirectCacheVisibility
            : DEFAULT_REDIRECT_CACHE_VISIBILITY;
    }

    public static function fromEnv(): self
    {
        return new self(
            redirectStatusCode: (int) EnvVars::REDIRECT_STATUS_CODE->loadFromEnv(),
            redirectCacheLifetime: (int) EnvVars::REDIRECT_CACHE_LIFETIME->loadFromEnv(),
            redirectCacheVisibility: EnvVars::REDIRECT_CACHE_VISIBILITY->loadFromEnv(),
        );
    }
}
