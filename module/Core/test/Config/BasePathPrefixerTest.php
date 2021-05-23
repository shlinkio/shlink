<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\BasePathPrefixer;

class BasePathPrefixerTest extends TestCase
{
    private BasePathPrefixer $prefixer;

    public function setUp(): void
    {
        $this->prefixer = new BasePathPrefixer();
    }

    /**
     * @test
     * @dataProvider provideConfig
     */
    public function parsesConfigAsExpected(
        array $originalConfig,
        array $expectedRoutes,
        array $expectedMiddlewares,
        string $expectedHostname,
    ): void {
        [
            'routes' => $routes,
            'middleware_pipeline' => $middlewares,
            'url_shortener' => $urlShortener,
        ] = ($this->prefixer)($originalConfig);

        self::assertEquals($expectedRoutes, $routes);
        self::assertEquals($expectedMiddlewares, $middlewares);
        self::assertEquals([
            'domain' => [
                'hostname' => $expectedHostname,
            ],
        ], $urlShortener);
    }

    public function provideConfig(): iterable
    {
        $urlShortener = [
            'domain' => [
                'hostname' => null,
            ],
        ];

        yield 'without anything' => [['url_shortener' => $urlShortener], [], [], ''];
        yield 'with empty options' => [
            [
                'routes' => [],
                'middleware_pipeline' => [],
                'url_shortener' => $urlShortener,
            ],
            [],
            [],
            '',
        ];
        yield 'with non-empty options' => [
            [
                'routes' => [
                    ['path' => '/something'],
                    ['path' => '/something-else'],
                ],
                'middleware_pipeline' => [
                    ['with' => 'no_path'],
                    ['path' => '/rest', 'middleware' => []],
                ],
                'url_shortener' => [
                    'domain' => [
                        'hostname' => 'doma.in',
                    ],
                ],
                'router' => ['base_path' => '/foo/bar'],
            ],
            [
                ['path' => '/foo/bar/something'],
                ['path' => '/foo/bar/something-else'],
            ],
            [
                ['with' => 'no_path'],
                ['path' => '/foo/bar/rest', 'middleware' => []],
            ],
            'doma.in/foo/bar',
        ];
    }
}
