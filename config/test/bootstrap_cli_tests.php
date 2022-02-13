<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\TestUtils;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$testHelper = $container->get(Helper\TestHelper::class);
$config = $container->get('config');
$em = $container->get(EntityManager::class);

$testHelper->createTestDb(['bin/cli', 'db:create'], ['bin/cli', 'db:migrate']);
CliTest\CliTestCase::setSeedFixturesCallback(
    static fn () => $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []),
);
