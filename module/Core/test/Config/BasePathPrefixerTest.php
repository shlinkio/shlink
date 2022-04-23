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
    ): void {
        ['routes' => $routes, 'middleware_pipeline' => $middlewares] = ($this->prefixer)($originalConfig);

        self::assertEquals($expectedRoutes, $routes);
        self::assertEquals($expectedMiddlewares, $middlewares);
    }

    public function provideConfig(): iterable
    {
        yield 'with empty options' => [['routes' => []], [], []];
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
        ];
    }
}
