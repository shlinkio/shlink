<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Common;

use Psr\Container\ContainerInterface;

use function file_exists;
use function touch;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$config = $container->get('config');

$container->get(TestHelper::class)->createTestDb($config['entity_manager']['connection']['path']);
DbTest\DatabaseTestCase::setEntityManager($container->get('em'));
