<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;

final readonly class NotFoundRedirectOptions implements NotFoundRedirectConfigInterface
{
    public function __construct(
        public string|null $invalidShortUrlRedirect = null,
        public string|null $regular404Redirect = null,
        public string|null $baseUrlRedirect = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            invalidShortUrlRedirect: EnvVars::DEFAULT_INVALID_SHORT_URL_REDIRECT->loadFromEnv(),
            regular404Redirect: EnvVars::DEFAULT_REGULAR_404_REDIRECT->loadFromEnv(),
            baseUrlRedirect: EnvVars::DEFAULT_BASE_URL_REDIRECT->loadFromEnv(),
        );
    }
}
