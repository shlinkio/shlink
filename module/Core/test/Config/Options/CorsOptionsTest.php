<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config\Options;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\Core\Config\Options\CorsOptions;

class CorsOptionsTest extends TestCase
{
    #[Test]
    #[TestWith(['*', '*', '*'])]
    #[TestWith(['<origin>', '<origin>', 'https://example.com'])]
    #[TestWith(['foo,bar, baz ', ['foo', 'bar', 'baz'], ''])]
    #[TestWith(['foo,bar,https://example.com', ['foo', 'bar', 'https://example.com'], 'https://example.com'])]
    public function expectedAccessControlAllowOriginIsSet(
        string $allowOrigins,
        string|array $expectedAllowOrigins,
        string $expectedAllowOriginsHeader,
    ): void {
        $options = new CorsOptions($allowOrigins);

        self::assertEquals($expectedAllowOrigins, $options->allowOrigins);
        self::assertEquals(
            $expectedAllowOriginsHeader,
            $options->responseWithAllowOrigin(
                ServerRequestFactory::fromGlobals()->withHeader('Origin', 'https://example.com'),
                new Response(),
            )->getHeaderLine('Access-Control-Allow-Origin'),
        );
    }
}
