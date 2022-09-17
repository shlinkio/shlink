<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use function sprintf;

final class AppOptions
{
    public function __construct(public string $name = 'Shlink', public string $version = '3.0.0')
    {
    }

    public function __toString(): string
    {
        return sprintf('%s:v%s', $this->name, $this->version);
    }
}
