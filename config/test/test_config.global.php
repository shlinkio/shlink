<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Runner\Version;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as Html;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as Xml;

use function Laminas\Stratigility\middleware;
use function Shlinkio\Shlink\Config\env;
use function sprintf;
use function sys_get_temp_dir;

use const ShlinkioTest\Shlink\SWOOLE_TESTING_HOST;
use const ShlinkioTest\Shlink\SWOOLE_TESTING_PORT;

$isApiTest = env('TEST_ENV') === 'api';
$generateCoverage = env('GENERATE_COVERAGE') === 'yes';
if ($isApiTest && $generateCoverage) {
    $filter = new Filter();
    $filter->includeDirectory(__DIR__ . '/../../module/Core/src');
    $filter->includeDirectory(__DIR__ . '/../../module/Rest/src');
    $coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
}

$buildDbConnection = static function (): array {
    $driver = env('DB_DRIVER', 'sqlite');
    $isCi = env('CI', false);
    $getCiMysqlPort = static fn (string $driver) => $driver === 'mysql' ? '3307' : '3308';

    return match ($driver) {
        'sqlite' => [
            'driver' => 'pdo_sqlite',
            'path' => sys_get_temp_dir() . '/shlink-tests.db',
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
                'log_file' => __DIR__ . '/../../data/log/api-tests/output.log',
                'enable_coroutine' => false,
            ],
        ],
    ],

    'routes' => !$isApiTest ? [] : [
        [
            'name' => 'dump_coverage',
            'path' => '/api-tests/stop-coverage',
            'middleware' => middleware(static function () use (&$coverage) {
                // TODO I have tried moving this block to a listener so that it's invoked automatically,
                //      but then the coverage is generated empty ¯\_(ツ)_/¯
                if ($coverage) { // @phpstan-ignore-line
                    $basePath = __DIR__ . '/../../build/coverage-api';

                    (new PHP())->process($coverage, $basePath . '.cov');
                    (new Xml(Version::getVersionString()))->process($coverage, $basePath . '/coverage-xml');
                    (new Html())->process($coverage, $basePath . '/coverage-html');
                }

                return new EmptyResponse();
            }),
            'allowed_methods' => ['GET'],
        ],
    ],

    'middleware_pipeline' => !$isApiTest ? [] : [
        'capture_code_coverage' => [
            'middleware' => middleware(static function (
                ServerRequestInterface $req,
                RequestHandlerInterface $handler,
            ) use (&$coverage): ResponseInterface {
                $coverage?->start($req->getHeaderLine('x-coverage-id'));

                try {
                    return $handler->handle($req);
                } finally {
                    $coverage?->stop();
                }
            }),
            'priority' => 9999,
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
