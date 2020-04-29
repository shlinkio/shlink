<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\SimplifiedConfigParser;

use function array_merge;

class SimplifiedConfigParserTest extends TestCase
{
    private SimplifiedConfigParser $postProcessor;

    public function setUp(): void
    {
        $this->postProcessor = new SimplifiedConfigParser();
    }

    /** @test */
    public function properlyMapsSimplifiedConfig(): void
    {
        $config = [
            'app_options' => [
                'disable_track_param' => 'foo',
            ],

            'entity_manager' => [
                'connection' => [
                    'driver' => 'mysql',
                    'host' => 'shlink_db',
                    'port' => '3306',
                ],
            ],
        ];
        $simplified = [
            'disable_track_param' => 'bar',
            'short_domain_schema' => 'https',
            'short_domain_host' => 'doma.in',
            'validate_url' => true,
            'delete_short_url_threshold' => 50,
            'invalid_short_url_redirect_to' => 'foobar.com',
            'regular_404_redirect_to' => 'bar.com',
            'base_url_redirect_to' => 'foo.com',
            'redis_servers' => [
                'tcp://1.1.1.1:1111',
                'tcp://1.2.2.2:2222',
            ],
            'db_config' => [
                'dbname' => 'shlink',
                'user' => 'foo',
                'password' => 'bar',
                'port' => '1234',
            ],
            'base_path' => '/foo/bar',
            'task_worker_num' => 50,
            'visits_webhooks' => [
                'http://my-api.com/api/v2.3/notify',
                'https://third-party.io/foo',
            ],
            'default_short_codes_length' => 8,
            'geolite_license_key' => 'kjh23ljkbndskj345',
        ];
        $expected = [
            'app_options' => [
                'disable_track_param' => 'bar',
            ],

            'entity_manager' => [
                'connection' => [
                    'driver' => 'mysql',
                    'host' => 'shlink_db',
                    'dbname' => 'shlink',
                    'user' => 'foo',
                    'password' => 'bar',
                    'port' => '1234',
                ],
            ],

            'url_shortener' => [
                'domain' => [
                    'schema' => 'https',
                    'hostname' => 'doma.in',
                ],
                'validate_url' => true,
                'visits_webhooks' => [
                    'http://my-api.com/api/v2.3/notify',
                    'https://third-party.io/foo',
                ],
                'default_short_codes_length' => 8,
            ],

            'delete_short_urls' => [
                'visits_threshold' => 50,
                'check_visits_threshold' => true,
            ],

            'dependencies' => [
                'aliases' => [
                    'lock_store' => 'redis_lock_store',
                ],
            ],

            'cache' => [
                'redis' => [
                    'servers' => [
                        'tcp://1.1.1.1:1111',
                        'tcp://1.2.2.2:2222',
                    ],
                ],
            ],

            'router' => [
                'base_path' => '/foo/bar',
            ],

            'not_found_redirects' => [
                'invalid_short_url' => 'foobar.com',
                'regular_404' => 'bar.com',
                'base_url' => 'foo.com',
            ],

            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'options' => [
                        'task_worker_num' => 50,
                    ],
                ],
            ],

            'geolite2' => [
                'license_key' => 'kjh23ljkbndskj345',
            ],
        ];

        $result = ($this->postProcessor)(array_merge($config, $simplified));

        $this->assertEquals(array_merge($expected, $simplified), $result);
    }
}
