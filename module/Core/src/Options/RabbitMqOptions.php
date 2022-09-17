<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

final class RabbitMqOptions
{
    public function __construct(
        public readonly bool $enabled = false,
        /** @deprecated */
        public readonly bool $legacyVisitsPublishing = false,
    ) {
    }
}
