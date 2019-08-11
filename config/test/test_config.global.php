<?php
declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use PDO;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ServiceManager\Factory\InvokableFactory;

use function Shlinkio\Shlink\Common\env;
use function sprintf;
use function sys_get_temp_dir;

$swooleTestingHost = '127.0.0.1';
$swooleTestingPort = 9999;

$buildDbConnection = function () {
    $driver = env('DB_DRIVER', 'sqlite');
    $isCi = env('TRAVIS', false);

    switch ($driver) {
        case 'sqlite':
            return [
                'driver' => 'pdo_sqlite',
                'path' => sys_get_temp_dir() . '/shlink-tests.db',
            ];
        case 'mysql':
            return [
                'driver' => 'pdo_mysql',
                'host' => $isCi ? '127.0.0.1' : 'shlink_db',
                'user' => 'root',
                'password' => $isCi ? '' : 'root',
                'dbname' => 'shlink_test',
                'charset' => 'utf8',
                'driverOptions' => [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                ],
            ];
        case 'postgres':
            return [
                'driver' => 'pdo_pgsql',
                'host' => $isCi ? '127.0.0.1' : 'shlink_db_postgres',
                'user' => 'postgres',
                'password' => $isCi ? '' : 'root',
                'dbname' => 'shlink_test',
                'charset' => 'utf8',
            ];
        default:
            return [];
    }
};

return [

    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'url_shortener' => [
        'domain' => [
            'schema' => 'http',
            'hostname' => 'doma.in',
        ],
    ],

    'zend-expressive-swoole' => [
        'swoole-http-server' => [
            'host' => $swooleTestingHost,
            'port' => $swooleTestingPort,
            'process-name' => 'shlink_test',
            'options' => [
                'pid_file' => sys_get_temp_dir() . '/shlink-test-swoole.pid',
                'worker_num' => 1,
                'task_worker_num' => 1,
            ],
        ],
    ],

    'dependencies' => [
        'services' => [
            'shlink_test_api_client' => new Client([
                'base_uri' => sprintf('http://%s:%s/', $swooleTestingHost, $swooleTestingPort),
                'http_errors' => false,
            ]),
        ],
        'factories' => [
            TestUtils\Helper\TestHelper::class => InvokableFactory::class,
        ],
    ],

    'entity_manager' => [
        'connection' => $buildDbConnection(),
    ],

    'data_fixtures' => [
        'paths' => [
            __DIR__ . '/../../module/Rest/test-api/Fixtures',
        ],
    ],

];
