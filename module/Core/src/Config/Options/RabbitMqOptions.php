<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

final readonly class RabbitMqOptions
{
    public function __construct(public bool $enabled = false)
    {
    }

    public static function fromEnv(): self
    {
        return new self((bool) EnvVars::RABBITMQ_ENABLED->loadFromEnv());
    }
}
