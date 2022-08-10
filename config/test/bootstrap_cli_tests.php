<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\TestUtils;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use function file_exists;
use function unlink;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$testHelper = $container->get(Helper\TestHelper::class);
$config = $container->get('config');
$em = $container->get(EntityManager::class);

// Delete old coverage in PHP, to avoid merging older executions with current one
$covFile = __DIR__ . '/../../build/coverage-cli.cov';
if (file_exists($covFile)) {
    unlink($covFile);
}

$testHelper->createTestDb(['bin/cli', 'db:create'], ['bin/cli', 'db:migrate']);
CliTest\CliTestCase::setSeedFixturesCallback(
    static fn () => $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []),
);
