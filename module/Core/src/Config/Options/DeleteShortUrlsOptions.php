<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

use const Shlinkio\Shlink\DEFAULT_DELETE_SHORT_URL_THRESHOLD;

final readonly class DeleteShortUrlsOptions
{
    public function __construct(
        public int $visitsThreshold = DEFAULT_DELETE_SHORT_URL_THRESHOLD,
        public bool $checkVisitsThreshold = true,
    ) {
    }

    public static function fromEnv(): self
    {
        $threshold = EnvVars::DELETE_SHORT_URL_THRESHOLD->loadFromEnv();

        return new self(
            visitsThreshold: (int) ($threshold ?? DEFAULT_DELETE_SHORT_URL_THRESHOLD),
            checkVisitsThreshold: $threshold !== null,
        );
    }
}
