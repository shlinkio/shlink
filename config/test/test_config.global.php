<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use GuzzleHttp\Client;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\ServiceManager\Factory\InvokableFactory;
use League\Event\EventDispatcher;
use Monolog\Level;
use PHPUnit\Runner\Version;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as Html;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SebastianBergmann\CodeCoverage\Report\Xml\Facade as Xml;
use Shlinkio\Shlink\Common\Logger\LoggerType;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use function file_exists;
use function Laminas\Stratigility\middleware;
use function Shlinkio\Shlink\Config\env;
use function sprintf;
use function sys_get_temp_dir;

use const ShlinkioTest\Shlink\SWOOLE_TESTING_HOST;
use const ShlinkioTest\Shlink\SWOOLE_TESTING_PORT;

$isApiTest = env('TEST_ENV') === 'api';
$isCliTest = env('TEST_ENV') === 'cli';
$isE2eTest = $isApiTest || $isCliTest;
$generateCoverage = env('GENERATE_COVERAGE') === 'yes';

$coverage = null;
if ($isE2eTest && $generateCoverage) {
    $filter = new Filter();
    $filter->includeDirectory(__DIR__ . '/../../module/Core/src');
    $filter->includeDirectory(__DIR__ . '/../../module/' . ($isApiTest ? 'Rest' : 'CLI') . '/src');
    $coverage = new CodeCoverage((new Selector())->forLineCoverage($filter), $filter);
}

/**
 * @param 'api'|'cli' $type
 */
$exportCoverage = static function (string $type = 'api') use (&$coverage): void {
    if ($coverage === null) {
        return;
    }

    $basePath = __DIR__ . '/../../build/coverage-' . $type;
    $covPath = $basePath . '.cov';

    // Every CLI test runs on its own process and dumps the coverage afterwards.
    // Try to load it and merge it, so that we end up with the whole coverage at the end.
    if ($type === 'cli' && file_exists($covPath)) {
        $coverage->merge(require $covPath);
    }

    (new PHP())->process($coverage, $covPath);
    (new Xml(Version::getVersionString()))->process($coverage, $basePath . '/coverage-xml');
    (new Html())->process($coverage, $basePath . '/coverage-html');
};

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

$buildTestLoggerConfig = static fn (string $filename) => [
    'level' => Level::Debug->value,
    'type' => LoggerType::STREAM->value,
    'destination' => sprintf('data/log/api-tests/%s', $filename),
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
            'middleware' => middleware(static function () use ($exportCoverage) {
                // TODO I have tried moving this block to a listener so that it's invoked automatically,
                //      but then the coverage is generated empty ¯\_(ツ)_/¯
                $exportCoverage();
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
        'delegators' => $isCliTest ? [
            Application::class => [
                static function (
                    ContainerInterface $c,
                    string $serviceName,
                    callable $callback,
                ) use (
                    &$coverage,
                    $exportCoverage,
                ) {
                    /** @var Application $app */
                    $app = $callback();
                    $wrappedEventDispatcher = new EventDispatcher();

                    // When the command starts, start collecting coverage
                    $wrappedEventDispatcher->subscribeTo(
                        ConsoleCommandEvent::class,
                        static function () use (&$coverage): void {
                            $id = env('COVERAGE_ID');
                            if ($id === null) {
                                return;
                            }

                            $coverage?->start($id);
                        },
                    );
                    // When the command ends, stop collecting coverage
                    $wrappedEventDispatcher->subscribeTo(
                        ConsoleTerminateEvent::class,
                        static function () use (&$coverage, $exportCoverage): void {
                            $id = env('COVERAGE_ID');
                            if ($id === null) {
                                return;
                            }

                            $coverage?->stop();
                            $exportCoverage('cli');
                        },
                    );

                    $app->setDispatcher(new class ($wrappedEventDispatcher) implements EventDispatcherInterface {
                        public function __construct(private EventDispatcher $wrappedDispatcher)
                        {
                        }

                        public function dispatch(object $event, ?string $eventName = null): object
                        {
                            $this->wrappedDispatcher->dispatch($event);
                            return $event;
                        }
                    });

                    return $app;
                },
            ],
        ] : [],
    ],

    'entity_manager' => [
        'connection' => $buildDbConnection(),
    ],

    'data_fixtures' => [
        'paths' => [
            // TODO These are used for CLI tests too, so maybe should be somewhere else
            __DIR__ . '/../../module/Rest/test-api/Fixtures',
        ],
    ],

    'logger' => [
        'Shlink' => $buildTestLoggerConfig('shlink.log'),
        'Access' => $buildTestLoggerConfig('access.log'),
    ],

];
