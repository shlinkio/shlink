<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation;

use Zend\Config\Factory;
use Zend\Stdlib\Glob;
use function Shlinkio\Shlink\Common\loadConfigFromGlob;

class ConfigProvider
{
    public function __invoke(): array
    {
        return loadConfigFromGlob(__DIR__ . '/../config/{,*.}config.php');
    }
}
