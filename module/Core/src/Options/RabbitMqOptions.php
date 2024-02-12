<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

final readonly class RabbitMqOptions
{
    public function __construct(
        public bool $enabled = false,
    ) {
    }
}
