<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Utils;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Symfony\Component\Process\Process;

class ApiTestsExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new EnvSpecificTestListener());
        $facade->registerSubscriber(new CleanDynamicEnvVarsTestListener());
    }

    public static function restartRRServer(): void
    {
        (new Process(['bin/rr', 'reset', '-c=config/roadrunner/.rr.test.yml']))->mustRun();
    }
}
