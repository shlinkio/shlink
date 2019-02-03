<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink;

use GuzzleHttp\Client;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ServiceManager\Factory\InvokableFactory;
use function sprintf;
use function sys_get_temp_dir;

$swooleTestingHost = '127.0.0.1';
$swooleTestingPort = 9999;

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
            Common\TestHelper::class => InvokableFactory::class,
        ],
    ],

    'entity_manager' => [
        'connection' => [
            'driver' => 'pdo_sqlite',
             'path' => sys_get_temp_dir() . '/shlink-tests.db',
//            'path' => __DIR__ . '/../../data/shlink-tests.db',
        ],
    ],

    'data_fixtures' => [
        'paths' => [
            __DIR__ . '/../../module/Rest/test-api/Fixtures',
        ],
    ],

];
