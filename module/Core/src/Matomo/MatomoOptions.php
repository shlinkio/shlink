<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

use Shlinkio\Shlink\Core\Config\EnvVars;

final readonly class MatomoOptions
{
    public function __construct(
        public bool $enabled = false,
        public string|null $baseUrl = null,
        public int|null $siteId = null,
        public string|null $apiToken = null,
        public int|null $visitTypeDimensionId = null,
        public int|null $isOrphanDimensionId = null,
        public int|null $visitDomainDimensionId = null,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            enabled: (bool) EnvVars::MATOMO_ENABLED->loadFromEnv(),
            baseUrl: EnvVars::MATOMO_BASE_URL->loadFromEnv(),
            siteId: self::optionalIntEnvVar(EnvVars::MATOMO_SITE_ID),
            apiToken: EnvVars::MATOMO_API_TOKEN->loadFromEnv(),
            visitTypeDimensionId: self::optionalIntEnvVar(EnvVars::MATOMO_VISIT_TYPE_DIMENSION_ID),
            isOrphanDimensionId: self::optionalIntEnvVar(EnvVars::MATOMO_IS_ORPHAN_DIMENSION_ID),
            visitDomainDimensionId: self::optionalIntEnvVar(EnvVars::MATOMO_VISIT_DOMAIN_DIMENSION_ID),
        );
    }

    private static function optionalIntEnvVar(EnvVars $envVar): int|null
    {
        // @mago-expect analysis:mixed-assignment
        $value = $envVar->loadFromEnv();
        return $value === null ? null : (int) $value;
    }
}
