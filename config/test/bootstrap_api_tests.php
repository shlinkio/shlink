<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\TestUtils;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;

use function register_shutdown_function;
use function sprintf;

use const ShlinkioTest\Shlink\SWOOLE_TESTING_HOST;
use const ShlinkioTest\Shlink\SWOOLE_TESTING_PORT;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$testHelper = $container->get(Helper\TestHelper::class);
$config = $container->get('config');
$em = $container->get(EntityManager::class);
$httpClient = $container->get('shlink_test_api_client');

// Dump code coverage when process shuts down
register_shutdown_function(function () use ($httpClient): void {
    $httpClient->request(
        'GET',
        sprintf('http://%s:%s/api-tests/stop-coverage', SWOOLE_TESTING_HOST, SWOOLE_TESTING_PORT),
    );
});

$testHelper->createTestDb(['bin/cli', 'db:create'], ['bin/cli', 'db:migrate']);
ApiTest\ApiTestCase::setApiClient($httpClient);
ApiTest\ApiTestCase::setSeedFixturesCallback(fn () => $testHelper->seedFixtures($em, $config['data_fixtures'] ?? []));
