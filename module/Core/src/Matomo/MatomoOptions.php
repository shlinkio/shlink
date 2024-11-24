<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Shlinkio\Shlink\Core\Config\EnvVars;

final readonly class MatomoOptions
{
    /**
     * @param numeric-string|int|null $siteId
     */
    public function __construct(
        public bool $enabled = false,
        public string|null $baseUrl = null,
        private string|int|null $siteId = null,
        public string|null $apiToken = null,
    ) {
    }

    public static function fromEnv(): self
    {
        return new self(
            enabled: (bool) EnvVars::MATOMO_ENABLED->loadFromEnv(),
            baseUrl: EnvVars::MATOMO_BASE_URL->loadFromEnv(),
            siteId: EnvVars::MATOMO_SITE_ID->loadFromEnv(),
            apiToken: EnvVars::MATOMO_API_TOKEN->loadFromEnv(),
        );
    }

    public function siteId(): int|null
    {
        if ($this->siteId === null) {
            return null;
        }

        // We enforce site ID to be hydrated as a numeric string or int, so it's safe to cast to int here
        return (int) $this->siteId;
    }
}
