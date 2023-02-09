<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config\PostProcessor;

use Mezzio\Router\Route;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Action\RedirectAction;
use Shlinkio\Shlink\Core\Config\PostProcessor\ShortUrlMethodsProcessor;

class ShortUrlMethodsProcessorTest extends TestCase
{
    private ShortUrlMethodsProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new ShortUrlMethodsProcessor();
    }

    /**
     * @test
     * @dataProvider provideConfigs
     */
    public function onlyFirstRouteIdentifiedAsRedirectIsEditedWithProperAllowedMethods(
        array $config,
        ?array $expectedRoutes,
    ): void {
        self::assertEquals($expectedRoutes, ($this->processor)($config)['routes'] ?? null);
    }

    public static function provideConfigs(): iterable
    {
        $buildConfigWithStatus = static fn (int $status, ?array $expectedAllowedMethods) => [[
            'routes' => [
                ['name' => 'foo'],
                ['name' => 'bar'],
                ['name' => RedirectAction::class],
            ],
            'redirects' => [
                'redirect_status_code' => $status,
            ],
        ], [
            ['name' => 'foo'],
            ['name' => 'bar'],
            [
                'name' => RedirectAction::class,
                'allowed_methods' => $expectedAllowedMethods,
            ],
        ]];

        yield 'empty config' => [[], null];
        yield 'empty routes' => [['routes' => []], []];
        yield 'no redirects route' => [['routes' => $routes = [
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]], $routes];
        yield 'one redirects route' => [['routes' => [
            ['name' => 'foo'],
            ['name' => 'bar'],
            ['name' => RedirectAction::class],
        ]], [
            ['name' => 'foo'],
            ['name' => 'bar'],
            [
                'name' => RedirectAction::class,
                'allowed_methods' => ['GET'],
            ],
        ]];
        yield 'one redirects route in different location' => [['routes' => [
            [
                'name' => RedirectAction::class,
                'allowed_methods' => ['POST'],
            ],
            ['name' => 'foo'],
            ['name' => 'bar'],
        ]], [
            ['name' => 'foo'],
            ['name' => 'bar'],
            [
                'name' => RedirectAction::class,
                'allowed_methods' => ['GET'],
            ],
        ]];
        yield 'multiple redirects routes' => [['routes' => [
            ['name' => RedirectAction::class],
            ['name' => 'foo'],
            ['name' => 'bar'],
            ['name' => RedirectAction::class],
            ['name' => RedirectAction::class],
        ]], [
            ['name' => 'foo'],
            ['name' => 'bar'],
            [
                'name' => RedirectAction::class,
                'allowed_methods' => ['GET'],
            ],
        ]];
        yield 'one redirects route with invalid status code' => $buildConfigWithStatus(500, ['GET']);
        yield 'one redirects route with 302 status code' => $buildConfigWithStatus(302, ['GET']);
        yield 'one redirects route with 301 status code' => $buildConfigWithStatus(301, ['GET']);
        yield 'one redirects route with 307 status code' => $buildConfigWithStatus(307, Route::HTTP_METHOD_ANY);
        yield 'one redirects route with 308 status code' => $buildConfigWithStatus(308, Route::HTTP_METHOD_ANY);
    }
}
