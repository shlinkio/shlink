<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Matomo;

class MatomoOptions
{
    public function __construct(
        public readonly bool $enabled = false,
        public readonly ?string $baseUrl = null,
        /** @var numeric-string|int|null */
        private readonly string|int|null $siteId = null,
        public readonly ?string $apiToken = null,
    ) {
    }

    public function siteId(): ?int
    {
        if ($this->siteId === null) {
            return null;
        }

        // We enforce site ID to be hydrated as a numeric string or int, so it's safe to cast to int here
        return (int) $this->siteId;
    }
}
