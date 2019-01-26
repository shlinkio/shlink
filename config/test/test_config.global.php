<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink;

use Zend\ServiceManager\Factory\InvokableFactory;
use function realpath;
use function sys_get_temp_dir;

return [

    'zend-expressive-swoole' => [
        'swoole-http-server' => [
            'port' => 9999,
        ],
    ],

    'dependencies' => [
        'factories' => [
            Common\TestHelper::class => InvokableFactory::class,
        ],
    ],

    'entity_manager' => [
        'connection' => [
            'driver' => 'pdo_sqlite',
            'path' => realpath(sys_get_temp_dir()) . '/shlink-tests.db',
        ],
    ],

];
