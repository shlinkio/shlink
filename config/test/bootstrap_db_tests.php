<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\TestUtils;

use Psr\Container\ContainerInterface;

use function file_exists;
use function touch;

// Create an empty .env file
if (! file_exists('.env')) {
    touch('.env');
}

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$container->get(Helper\TestHelper::class)->createTestDb();
DbTest\DatabaseTestCase::setEntityManager($container->get('em'));
