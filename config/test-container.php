<?php
declare(strict_types=1);

$container = include __DIR__ . '/container.php';

$container->setAllowOverride(true);
$config = $container->get('config');
$config['entity_manager']['connection'] = [
    'driver' => 'pdo_sqlite',
    'path' => realpath(sys_get_temp_dir()) . '/shlink-tests.db',
];
$container->setService('config', $config);

return $container;
