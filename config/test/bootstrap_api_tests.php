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
$httpClient = $container->get('shlink_test_api_client');

$testHelper->createTestDb(
    createDbCommand: ['bin/cli', 'db:create'],
    migrateDbCommand: ['bin/cli', 'db:migrate'],
    dropSchemaCommand: ['bin/doctrine', 'orm:schema-tool:drop'],
    runSqlCommand: ['bin/doctrine', 'dbal:run-sql'],
);
ApiTest\ApiTestCase::setApiClient($httpClient);
ApiTest\ApiTestCase::setSeedFixturesCallback(fn () => $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []));
