<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Installer\Config;

use Shlinkio\Shlink\Installer\Config\Plugin\ConfigCustomizerInterface;
use Zend\ServiceManager\AbstractPluginManager;

class ConfigCustomizerManager extends AbstractPluginManager implements ConfigCustomizerManagerInterface
{
    protected $instanceOf = ConfigCustomizerInterface::class;
}
