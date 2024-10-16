<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

use function count;
use function Shlinkio\Shlink\Core\splitByComma;

final readonly class RobotsOptions
{
    /**
     * @param string[] $userAgents
     */
    public function __construct(public bool $allowAllShortUrls = false, public array $userAgents = [])
    {
    }

    public static function fromEnv(): self
    {
        return new self(
            allowAllShortUrls: (bool) EnvVars::ROBOTS_ALLOW_ALL_SHORT_URLS->loadFromEnv(),
            userAgents: splitByComma(EnvVars::ROBOTS_USER_AGENTS->loadFromEnv()),
        );
    }

    public function hasUserAgents(): bool
    {
        return count($this->userAgents) > 0;
    }
}
