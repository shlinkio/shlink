<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\ShortUrlOptionsPayloadMiddleware;

class ShortUrlOptionsPayloadMiddlewareTest extends TestCase
{
    private ShortUrlOptionsPayloadMiddleware $middleware;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new ShortUrlOptionsPayloadMiddleware(new UrlShortenerOptions(defaultShortCodesLength: 8));
    }

    #[Test, DataProvider('provideBodies')]
    public function defaultValueIsInjectedInBodyWhenNotProvided(array $body, int $expectedLength): void
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);
        $this->handler->expects($this->once())->method('handle')->with($this->callback(
            static function (ServerRequestInterface $req) use ($expectedLength) {
                $parsedBody = (array) $req->getParsedBody();

                Assert::assertArrayHasKey('shortCodeLength', $parsedBody);
                Assert::assertEquals($expectedLength, $parsedBody['shortCodeLength']);

                Assert::assertArrayHasKey('shortUrlMode', $parsedBody);
                Assert::assertArrayHasKey('multiSegmentSlugsEnabled', $parsedBody);

                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public static function provideBodies(): iterable
    {
        yield 'value provided' => [['shortCodeLength' => 6], 6];
        yield 'value not provided' => [[], 8];
    }
}
