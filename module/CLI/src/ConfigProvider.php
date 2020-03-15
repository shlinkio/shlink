<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI;

use function Shlinkio\Shlink\Config\loadConfigFromGlob;

class ConfigProvider
{
    public function __invoke(): array
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }
}
