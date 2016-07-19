<?php
namespace Shlinkio\Shlink\CLI\Config;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;

class ConfigProvider
{
    public function __invoke()
    {
        return Factory::fromFiles(Glob::glob(__DIR__ . '/../../config/{,*.}config.php', Glob::GLOB_BRACE));
    }
}
