<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\Options;

use Shlinkio\Shlink\Core\Config\EnvVars;

use function sprintf;

final class AppOptions
{
    public function __construct(public string $name = 'Shlink', public string $version = '4.0.0')
    {
    }

    public static function fromEnv(): self
    {
        $version = EnvVars::isDevEnv() ? 'latest' : '%SHLINK_VERSION%';
        return new self(version: $version);
    }

    public function __toString(): string
    {
        return sprintf('%s:v%s', $this->name, $this->version);
    }
}
