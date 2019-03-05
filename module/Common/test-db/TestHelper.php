<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class TestHelper
{
    public function createTestDb(): void
    {
        $process = new Process(['vendor/bin/doctrine', 'orm:schema-tool:drop', '--force', '--no-interaction', '-q']);
        $process->inheritEnvironmentVariables()
                ->mustRun();

        $process = new Process(['vendor/bin/doctrine', 'orm:schema-tool:create', '--no-interaction', '-q']);
        $process->inheritEnvironmentVariables()
                ->mustRun();
    }

    public function seedFixtures(EntityManagerInterface $em, array $config): void
    {
        $paths = $config['paths'] ?? [];
        if (empty($paths)) {
            return;
        }

        $loader = new Loader();
        foreach ($paths as $path) {
            $loader->loadFromDirectory($path);
        }

        $executor = new ORMExecutor($em, new ORMPurger());
        $executor->execute($loader->getFixtures());
    }
}
