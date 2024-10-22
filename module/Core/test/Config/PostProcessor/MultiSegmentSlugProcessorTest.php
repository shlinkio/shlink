<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config\PostProcessor;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\EnvVars;
use Shlinkio\Shlink\Core\Config\PostProcessor\MultiSegmentSlugProcessor;

use function putenv;

class MultiSegmentSlugProcessorTest extends TestCase
{
    private MultiSegmentSlugProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new MultiSegmentSlugProcessor();
    }

    protected function tearDown(): void
    {
        putenv(EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->value);
    }

    #[Test, DataProvider('provideConfigs')]
    public function parsesRoutesAsExpected(bool $multiSegmentEnabled, array $routes, array $expectedRoutes): void
    {
        putenv(EnvVars::MULTI_SEGMENT_SLUGS_ENABLED->value . '=' . ($multiSegmentEnabled ? 'true' : 'false'));
        self::assertEquals($expectedRoutes, ($this->processor)(['routes' => $routes])['routes'] ?? []);
    }

    public static function provideConfigs(): iterable
    {
        yield [
            false,
            $routes = [
                ['path' => '/foo'],
                ['path' => '/bar/{shortCode}'],
                ['path' => '/baz/{shortCode}/foo'],
            ],
            $routes,
        ];
        yield [
            true,
            [
                ['path' => '/foo'],
                ['path' => '/bar/{shortCode}'],
                ['path' => '/baz/{shortCode}/foo'],
            ],
            [
                ['path' => '/foo'],
                ['path' => '/bar/{shortCode:.+}'],
                ['path' => '/baz/{shortCode:.+}/foo'],
            ],
        ];
    }
}
