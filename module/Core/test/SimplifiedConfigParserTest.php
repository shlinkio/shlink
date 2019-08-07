<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\SimplifiedConfigParser;

use function array_merge;

class SimplifiedConfigParserTest extends TestCase
{
    private $postProcessor;

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
            'validate_url' => false,
            'delete_short_url_threshold' => 50,
            'locale' => 'es',
            'not_found_redirect_to' => 'foobar.com',
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
                'validate_url' => false,
                'not_found_short_url' => [
                    'redirect_to' => 'foobar.com',
                    'enable_redirection' => true,
                ],
            ],

            'translator' => [
                'locale' => 'es',
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

            'redis' => [
                'servers' => [
                    'tcp://1.1.1.1:1111',
                    'tcp://1.2.2.2:2222',
                ],
            ],
        ];

        $result = ($this->postProcessor)(array_merge($config, $simplified));

        $this->assertEquals(array_merge($expected, $simplified), $result);
    }
}
