<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

class ConfigProvider
{
    public function __invoke(): array
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }
}
