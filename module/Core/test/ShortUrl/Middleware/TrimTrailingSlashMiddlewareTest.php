<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Core\ShortUrl\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;
use Shlinkio\Shlink\Core\ShortUrl\Middleware\TrimTrailingSlashMiddleware;

use function Functional\compose;
use function Functional\const_function;

class TrimTrailingSlashMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $requestHandler;

    protected function setUp(): void
    {
        $this->requestHandler = $this->prophesize(RequestHandlerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideRequests
     */
    public function returnsExpectedResponse(
        bool $trailingSlashEnabled,
        ServerRequestInterface $inputRequest,
        callable $assertions,
    ): void {
        $arg = compose($assertions, const_function(true));

        $this->requestHandler->handle(Argument::that($arg))->willReturn(new Response());
        $this->middleware($trailingSlashEnabled)->process($inputRequest, $this->requestHandler->reveal());
    }

    public function provideRequests(): iterable
    {
        yield 'trailing slash disabled' => [
            false,
            $inputReq = ServerRequestFactory::fromGlobals(),
            function (ServerRequestInterface $request) use ($inputReq): void {
                Assert::assertSame($inputReq, $request);
            },
        ];
        yield 'trailing slash enabled without shortCode attr' => [
            true,
            $inputReq = ServerRequestFactory::fromGlobals(),
            function (ServerRequestInterface $request) use ($inputReq): void {
                Assert::assertSame($inputReq, $request);
            },
        ];
        yield 'trailing slash enabled with null shortCode attr' => [
            true,
            $inputReq = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', null),
            function (ServerRequestInterface $request) use ($inputReq): void {
                Assert::assertSame($inputReq, $request);
            },
        ];
        yield 'trailing slash enabled with non-null shortCode attr' => [
            true,
            $inputReq = ServerRequestFactory::fromGlobals()->withAttribute('shortCode', 'foo//'),
            function (ServerRequestInterface $request) use ($inputReq): void {
                Assert::assertNotSame($inputReq, $request);
                Assert::assertEquals('foo', $request->getAttribute('shortCode'));
            },
        ];
    }

    private function middleware(bool $trailingSlashEnabled = false): TrimTrailingSlashMiddleware
    {
        return new TrimTrailingSlashMiddleware(new UrlShortenerOptions(trailingSlashEnabled: $trailingSlashEnabled));
    }
}
