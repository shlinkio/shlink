<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

use function file_exists;
use function touch;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$testHelper = $container->get(TestHelper::class);
$config = $container->get('config');
$em = $container->get(EntityManager::class);

$testHelper->createTestDb($config['entity_manager']['connection']['path']);
ApiTest\ApiTestCase::setApiClient($container->get('shlink_test_api_client'));
ApiTest\ApiTestCase::setSeedFixturesCallback(function () use ($testHelper, $em, $config) {
    $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []);
});
