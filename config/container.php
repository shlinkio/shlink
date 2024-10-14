<?php

declare(strict_types=1);

use Laminas\ServiceManager\ServiceManager;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Symfony\Component\Lock;

use function Shlinkio\Shlink\Config\loadEnvVarsFromConfig;
use function Shlinkio\Shlink\Core\enumValues;

use const Shlinkio\Shlink\LOCAL_LOCK_FACTORY;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

// Promote env vars from installer config
loadEnvVarsFromConfig('config/params/generated_config.php', enumValues(EnvVars::class));

// This is one of the first files loaded. Configure the timezone and memory limit here
ini_set('memory_limit', EnvVars::MEMORY_LIMIT->loadFromEnv());
date_default_timezone_set(EnvVars::TIMEZONE->loadFromEnv());

// This class alias tricks the ConfigAbstractFactory to return Lock\Factory instances even with a different service name
// It needs to be placed here as individual config files will not be loaded once config is cached
if (! class_exists(LOCAL_LOCK_FACTORY)) {
    class_alias(Lock\LockFactory::class, LOCAL_LOCK_FACTORY);
}

return (static function (): ServiceManager {
    $config = require __DIR__ . '/config.php';
    $container = new ServiceManager($config['dependencies']);
    $container->setService('config', $config);

    return $container;
})();
