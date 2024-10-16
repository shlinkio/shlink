<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Fig\Http\Message\StatusCodeInterface;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Util\RedirectStatus;

use const Shlinkio\Shlink\DEFAULT_REDIRECT_CACHE_LIFETIME;
use const Shlinkio\Shlink\DEFAULT_REDIRECT_STATUS_CODE;

final readonly class RedirectOptions
{
    public RedirectStatus $redirectStatusCode;
    public int $redirectCacheLifetime;

    public function __construct(
        int $redirectStatusCode = StatusCodeInterface::STATUS_FOUND,
        int $redirectCacheLifetime = DEFAULT_REDIRECT_CACHE_LIFETIME,
    ) {
        $this->redirectStatusCode = RedirectStatus::tryFrom($redirectStatusCode) ?? DEFAULT_REDIRECT_STATUS_CODE;
        $this->redirectCacheLifetime = $redirectCacheLifetime > 0
            ? $redirectCacheLifetime
            : DEFAULT_REDIRECT_CACHE_LIFETIME;
    }

    public static function fromEnv(): self
    {
        return new self(
            redirectStatusCode: (int) EnvVars::REDIRECT_STATUS_CODE->loadFromEnv(),
            redirectCacheLifetime: (int) EnvVars::REDIRECT_CACHE_LIFETIME->loadFromEnv(),
        );
    }
}
