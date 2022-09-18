<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use const Shlinkio\Shlink\DEFAULT_DELETE_SHORT_URL_THRESHOLD;

final class DeleteShortUrlsOptions
{
    public function __construct(
        public readonly int $visitsThreshold = DEFAULT_DELETE_SHORT_URL_THRESHOLD,
        public readonly bool $checkVisitsThreshold = true,
    ) {
    }
}
