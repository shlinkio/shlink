<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use function count;

final readonly class RobotsOptions
{
    public function __construct(
        public bool $allowAllShortUrls = false,
        /** @var string[] */
        public array $userAgents = [],
    ) {
    }

    public function hasUserAgents(): bool
    {
        return count($this->userAgents) > 0;
    }
}
