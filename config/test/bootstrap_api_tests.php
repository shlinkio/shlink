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

$testHelper->createTestDb();
ApiTest\ApiTestCase::setApiClient($container->get('shlink_test_api_client'));
ApiTest\ApiTestCase::setSeedFixturesCallback(function () use ($testHelper, $em, $config) {
    $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []);
});
