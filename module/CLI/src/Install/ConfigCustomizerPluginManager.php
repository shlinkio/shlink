<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install;

use Shlinkio\Shlink\CLI\Install\Plugin\ConfigCustomizerInterface;
use Zend\ServiceManager\AbstractPluginManager;

class ConfigCustomizerPluginManager extends AbstractPluginManager implements ConfigCustomizerPluginManagerInterface
{
    protected $instanceOf = ConfigCustomizerInterface::class;
}
