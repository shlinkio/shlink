<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\EventDispatcher;

use function Shlinkio\Shlink\Common\loadConfigFromGlob;

class ConfigProvider
{
    public function __invoke()
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }
}
