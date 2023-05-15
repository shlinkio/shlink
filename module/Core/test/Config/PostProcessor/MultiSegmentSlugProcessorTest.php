<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config\PostProcessor;

use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\PostProcessor\MultiSegmentSlugProcessor;

class MultiSegmentSlugProcessorTest extends TestCase
{
    private MultiSegmentSlugProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new MultiSegmentSlugProcessor();
    }

    /**
     * @test
     * @dataProvider provideConfigs
     */
    public function parsesRoutesAsExpected(array $config, array $expectedRoutes): void
    {
        self::assertEquals($expectedRoutes, ($this->processor)($config)['routes'] ?? []);
    }

    public function provideConfigs(): iterable
    {
        yield [[], []];
        yield [['url_shortener' => []], []];
        yield [['url_shortener' => ['multi_segment_slugs_enabled' => false]], []];
        yield [
            [
                'url_shortener' => ['multi_segment_slugs_enabled' => false],
                'routes' => $routes = [
                    ['path' => '/foo'],
                    ['path' => '/bar/{shortCode}'],
                    ['path' => '/baz/{shortCode}/foo'],
                ],
            ],
            $routes,
        ];
        yield [
            [
                'url_shortener' => ['multi_segment_slugs_enabled' => true],
                'routes' => [
                    ['path' => '/foo'],
                    ['path' => '/bar/{shortCode}'],
                    ['path' => '/baz/{shortCode}/foo'],
                ],
            ],
            [
                ['path' => '/foo'],
                ['path' => '/bar/{shortCode:.+}'],
                ['path' => '/baz/{shortCode:.+}/foo'],
            ],
        ];
    }
}
