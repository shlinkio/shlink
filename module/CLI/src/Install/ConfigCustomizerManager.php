<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Install;

use Shlinkio\Shlink\CLI\Install\Plugin\ConfigCustomizerInterface;
use Zend\ServiceManager\AbstractPluginManager;

class ConfigCustomizerManager extends AbstractPluginManager implements ConfigCustomizerManagerInterface
{
    protected $instanceOf = ConfigCustomizerInterface::class;
}
