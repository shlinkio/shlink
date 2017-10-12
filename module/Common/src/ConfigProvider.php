<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;

class ConfigProvider
{
    public function __invoke()
    {
        return Factory::fromFiles(Glob::glob(__DIR__ . '/../config/{,*.}config.php', Glob::GLOB_BRACE));
    }
}
