<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\SimplifiedConfigParser;

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
            'base_path' => '/foo/bar',
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

            'router' => [
                'base_path' => '/foo/bar',
            ],

            'not_found_redirects' => [
                'invalid_short_url' => 'foobar.com',
            ],
        ];

        $result = ($this->postProcessor)(array_merge($config, $simplified));

        $this->assertEquals(array_merge($expected, $simplified), $result);
    }

    /**
     * @test
     * @dataProvider provideConfigWithDeprecates
     */
    public function properlyMapsDeprecatedConfigs(array $config, string $expected): void
    {
        $result = ($this->postProcessor)($config);
        $this->assertEquals($expected, $result['not_found_redirects']['invalid_short_url']);
    }

    public function provideConfigWithDeprecates(): iterable
    {
        yield 'only deprecated config' => [['not_found_redirect_to' => 'old_value'], 'old_value'];
        yield 'only new config' => [['invalid_short_url_redirect_to' => 'new_value'], 'new_value'];
        yield 'both configs, new first' => [
            ['invalid_short_url_redirect_to' => 'new_value', 'not_found_redirect_to' => 'old_value'],
            'new_value',
        ];
        yield 'both configs, deprecated first' => [
            ['not_found_redirect_to' => 'old_value', 'invalid_short_url_redirect_to' => 'new_value'],
            'new_value',
        ];
    }
}
