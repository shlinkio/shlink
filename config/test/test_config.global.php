<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Mezzio\Router\FastRouteRouter;
use Monolog\Level;
use Shlinkio\Shlink\Common\Logger\LoggerType;
use Shlinkio\Shlink\TestUtils\ApiTest\CoverageMiddleware;
use Shlinkio\Shlink\TestUtils\CliTest\CliCoverageDelegator;
use Shlinkio\Shlink\TestUtils\Helper\CoverageHelper;
use Symfony\Component\Console\Application;

use function Laminas\Stratigility\middleware;
use function Shlinkio\Shlink\Config\env;
use function sleep;
use function sprintf;

use const ShlinkioTest\Shlink\API_TESTS_HOST;
use const ShlinkioTest\Shlink\API_TESTS_PORT;

$testEnv = env('TEST_ENV');
$isApiTest = $testEnv === 'api';
$isCliTest = $testEnv === 'cli';
$isE2eTest = $isApiTest || $isCliTest;

$coverageType = env('GENERATE_COVERAGE');
$generateCoverage = $coverageType === 'yes';
$coverage = $isE2eTest && $generateCoverage ? CoverageHelper::createCoverageForDirectories(
    [
        __DIR__ . '/../../module/Core/src',
        __DIR__ . '/../../module/' . ($isApiTest ? 'Rest' : 'CLI') . '/src',
    ],
    __DIR__ . '/../../build/coverage-' . $testEnv,
) : null;

$buildDbConnection = static function (): array {
    $driver = env('DB_DRIVER', 'sqlite');
    $isCi = env('CI', false);
    $getCiMysqlPort = static fn (string $driver) => $driver === 'mysql' ? '3307' : '3308';

    return match ($driver) {
        'sqlite' => [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ],
        'postgres' => [
            'driver' => 'pdo_pgsql',
            'host' => $isCi ? '127.0.0.1' : 'shlink_db_postgres',
            'port' => $isCi ? '5434' : '5432',
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
            'driverOptions' => [
                'TrustServerCertificate' => 'true',
            ],
        ],
        default => [ // mysql and maria
            'driver' => 'pdo_mysql',
            'host' => $isCi ? '127.0.0.1' : sprintf('shlink_db_%s', $driver),
            'port' => $isCi ? $getCiMysqlPort($driver) : '3306',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'shlink_test',
            'charset' => 'utf8mb4',
        ],
    };
};

$buildTestLoggerConfig = static fn (string $filename) => [
    'level' => Level::Debug->value,
    'type' => LoggerType::STREAM->value,
    'destination' => sprintf('data/log/api-tests/%s', $filename),
    'add_new_line' => true,
];

return [

    'debug' => true,
    ConfigAggregator::ENABLE_CACHE => false,
    FastRouteRouter::CONFIG_CACHE_ENABLED => false,

    'routes' => [
        // This route is used to test that title resolution is skipped if the long URL times out
        [
            'name' => 'long_url_with_timeout',
            'path' => '/api-tests/long-url-with-timeout',
            'allowed_methods' => ['GET'],
            'middleware' => middleware(static function () {
                sleep(5); // Title resolution times out at 3 seconds
                return new HtmlResponse('<title>The title</title>');
            }),
        ],
    ],

    'middleware_pipeline' => !$isApiTest ? [] : [
        'capture_code_coverage' => [
            'middleware' => new CoverageMiddleware($coverage),
            'priority' => 9999,
        ],
    ],

    'dependencies' => [
        'services' => [
            'shlink_test_api_client' => new Client([
                'base_uri' => sprintf('http://%s:%s/', API_TESTS_HOST, API_TESTS_PORT),
                'http_errors' => false,
            ]),
        ],
        'factories' => [
            TestUtils\Helper\TestHelper::class => InvokableFactory::class,
        ],
        'delegators' => $isCliTest ? [
            Application::class => [
                new CliCoverageDelegator($coverage),
            ],
        ] : [],
    ],

    'entity_manager' => [
        'connection' => $buildDbConnection(),
    ],

    'data_fixtures' => [
        'paths' => [
            // TODO These are used for other module's tests, so maybe should be somewhere else
            __DIR__ . '/../../module/Rest/test-api/Fixtures',
        ],
    ],

    'logger' => [
        'Shlink' => $buildTestLoggerConfig('shlink.log'),
        'Access' => $buildTestLoggerConfig('access.log'),
    ],

];
