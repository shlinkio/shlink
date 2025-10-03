<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\Config\Options;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
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
            $this->responseFromOptions($options)->getHeaderLine('Access-Control-Allow-Origin'),
        );
    }

    #[Test]
    #[TestWith([true])]
    #[TestWith([false])]
    public function expectedAccessControlAllowCredentialsIsSet(bool $allowCredentials): void
    {
        $options = new CorsOptions(allowCredentials: $allowCredentials);
        $resp = $this->responseFromOptions($options);

        if ($allowCredentials) {
            self::assertEquals('true', $resp->getHeaderLine('Access-Control-Allow-Credentials'));
        } else {
            self::assertFalse($resp->hasHeader('Access-Control-Allow-Credentials'));
        }
    }

    private function responseFromOptions(CorsOptions $options): ResponseInterface
    {
        return $options->responseWithCorsHeaders(
            ServerRequestFactory::fromGlobals()->withHeader('Origin', 'https://example.com'),
            new Response(),
        );
    }
}
