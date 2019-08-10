<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;

class ConfigProvider
{
    public function __invoke(): array
    {
        return Factory::fromFiles(Glob::glob(__DIR__ . '/../config/{,*.}config.php', Glob::GLOB_BRACE));
    }
}
