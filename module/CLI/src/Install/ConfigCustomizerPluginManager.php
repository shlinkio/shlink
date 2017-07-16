<?php
namespace Shlinkio\Shlink\CLI\Install;

use Shlinkio\Shlink\CLI\Install\Plugin\ConfigCustomizerPluginInterface;
use Zend\ServiceManager\AbstractPluginManager;

class ConfigCustomizerPluginManager extends AbstractPluginManager implements ConfigCustomizerPluginManagerInterface
{
    protected $instanceOf = ConfigCustomizerPluginInterface::class;
}
