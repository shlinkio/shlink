<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ServiceManager\Factory\InvokableFactory;
use PDO;

use function Shlinkio\Shlink\Common\env;
use function sprintf;
use function sys_get_temp_dir;

$swooleTestingHost = '127.0.0.1';
$swooleTestingPort = 9999;

$buildDbConnection = function (): array {
    $driver = env('DB_DRIVER', 'sqlite');
    $isCi = env('TRAVIS', false);
    $getMysqlHost = fn (string $driver) => sprintf('shlink_db%s', $driver === 'mysql' ? '' : '_maria');

    $driverConfigMap = [
        'sqlite' => [
            'driver' => 'pdo_sqlite',
            'path' => sys_get_temp_dir() . '/shlink-tests.db',
        ],
        'mysql' => [
            'driver' => 'pdo_mysql',
            'host' => $isCi ? '127.0.0.1' : $getMysqlHost($driver),
            'user' => 'root',
            'password' => $isCi ? '' : 'root',
            'dbname' => 'shlink_test',
            'charset' => 'utf8',
            'driverOptions' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
            ],
        ],
        'postgres' => [
            'driver' => 'pdo_pgsql',
            'host' => $isCi ? '127.0.0.1' : 'shlink_db_postgres',
            'user' => 'postgres',
            'password' => $isCi ? '' : 'root',
            'dbname' => 'shlink_test',
            'charset' => 'utf8',
        ],
    ];
    $driverConfigMap['maria'] = $driverConfigMap['mysql'];

    return $driverConfigMap[$driver] ?? [];
};

return [

    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,

    'url_shortener' => [
        'domain' => [
            'schema' => 'http',
            'hostname' => 'doma.in',
        ],
        'validate_url' => true,
    ],

    'mezzio-swoole' => [
        'enable_coroutine' => false,
        'swoole-http-server' => [
            'host' => $swooleTestingHost,
            'port' => $swooleTestingPort,
            'process-name' => 'shlink_test',
            'options' => [
                'pid_file' => sys_get_temp_dir() . '/shlink-test-swoole.pid',
                'worker_num' => 1,
                'task_worker_num' => 1,
                'enable_coroutine' => false,
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
