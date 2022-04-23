<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\TestUtils;

use Psr\Container\ContainerInterface;

/** @var ContainerInterface $container */
$container = require __DIR__ . '/../container.php';
$container->get(Helper\TestHelper::class)->createTestDb(['bin/cli', 'db:create'], ['bin/cli', 'db:migrate']);
DbTest\DatabaseTestCase::setEntityManager($container->get('em'));
