<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\Stdlib\Glob;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Runner\Version;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as Xml;

use function Laminas\Stratigility\middleware;
use function Shlinkio\Shlink\Common\env;
use function sprintf;
use function sys_get_temp_dir;

use const ShlinkioTest\Shlink\SWOOLE_TESTING_HOST;
use const ShlinkioTest\Shlink\SWOOLE_TESTING_PORT;

$isApiTest = env('TEST_ENV') === 'api';
if ($isApiTest) {
    $filter = new Filter();
    foreach (Glob::glob(__DIR__ . '/../../module/*/src') as $item) {
        $filter->includeDirectory($item);
    }
    $coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
}

$buildDbConnection = function (): array {
    $driver = env('DB_DRIVER', 'sqlite');
    $isCi = env('CI', false);
    $getMysqlHost = fn (string $driver) => sprintf('shlink_db%s', $driver === 'mysql' ? '' : '_maria');
    $getCiMysqlPort = fn (string $driver) => $driver === 'mysql' ? '3307' : '3308';

    $driverConfigMap = [
        'sqlite' => [
            'driver' => 'pdo_sqlite',
            'path' => sys_get_temp_dir() . '/shlink-tests.db',
        ],
        'mysql' => [
            'driver' => 'pdo_mysql',
            'host' => $isCi ? '127.0.0.1' : $getMysqlHost($driver),
            'port' => $isCi ? $getCiMysqlPort($driver) : '3306',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'shlink_test',
            'charset' => 'utf8',
        ],
        'postgres' => [
            'driver' => 'pdo_pgsql',
            'host' => $isCi ? '127.0.0.1' : 'shlink_db_postgres',
            'port' => $isCi ? '5433' : '5432',
            'user' => 'postgres',
            'password' => 'root',
            'dbname' => 'shlink_test',
            'charset' => 'utf8',
        ],
        'mssql' => [
            'driver' => 'pdo_sqlsrv',
            'host' => $isCi ? '127.0.0.1' : 'shlink_db_ms',
            'user' => 'sa',
            'password' => 'Passw0rd!',
            'dbname' => 'shlink_test',
        ],
    ];
    $driverConfigMap['maria'] = $driverConfigMap['mysql'];

    return $driverConfigMap[$driver] ?? [];
};

$buildTestLoggerConfig = fn (string $handlerName, string $filename) => [
    'handlers' => [
        $handlerName => [
            'name' => StreamHandler::class,
            'params' => [
                'level' => Logger::DEBUG,
                'stream' => sprintf('data/log/api-tests/%s', $filename),
            ],
        ],
    ],
];

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
            'host' => SWOOLE_TESTING_HOST,
            'port' => SWOOLE_TESTING_PORT,
            'process-name' => 'shlink_test',
            'options' => [
                'pid_file' => sys_get_temp_dir() . '/shlink-test-swoole.pid',
                'enable_coroutine' => false,
            ],
        ],
    ],

    'routes' => !$isApiTest ? [] : [
        [
            'name' => 'start_collecting_coverage',
            'path' => '/api-tests/start-coverage',
            'middleware' => middleware(static function () use (&$coverage) {
                if ($coverage) {
                    $coverage->start('API tests');
                }
                return new EmptyResponse();
            }),
            'allowed_methods' => ['GET'],
        ],
        [
            'name' => 'dump_coverage',
            'path' => '/api-tests/stop-coverage',
            'middleware' => middleware(static function () use (&$coverage) {
                if ($coverage) {
                    $basePath = __DIR__ . '/../../build/coverage-api';
                    $coverage->stop();
                    (new PHP())->process($coverage, $basePath . '.cov');
                    (new Xml(Version::getVersionString()))->process($coverage, $basePath . '/coverage-xml');
                }

                return new EmptyResponse();
            }),
            'allowed_methods' => ['GET'],
        ],
    ],

    'mercure' => [
        'public_hub_url' => null,
        'internal_hub_url' => null,
        'jwt_secret' => null,
    ],

    'dependencies' => [
        'services' => [
            'shlink_test_api_client' => new Client([
                'base_uri' => sprintf('http://%s:%s/', SWOOLE_TESTING_HOST, SWOOLE_TESTING_PORT),
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

    'logger' => [
        'Shlink' => $buildTestLoggerConfig('shlink_handler', 'shlink.log'),
        'Access' => $buildTestLoggerConfig('access_handler', 'access.log'),
    ],

];
