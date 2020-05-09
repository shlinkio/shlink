<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlMetaInputFilter;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\DefaultShortCodesLengthMiddleware;

class DefaultShortCodesLengthMiddlewareTest extends TestCase
{
    private DefaultShortCodesLengthMiddleware $middleware;
    private ObjectProphecy $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new DefaultShortCodesLengthMiddleware(8);
    }

    /**
     * @test
     * @dataProvider provideBodies
     */
    public function defaultValueIsInjectedInBodyWhenNotProvided(array $body, int $expectedLength): void
    {
        $request = ServerRequestFactory::fromGlobals()->withParsedBody($body);
        $handle = $this->handler->handle(Argument::that(function (ServerRequestInterface $req) use ($expectedLength) {
            $parsedBody = $req->getParsedBody();
            Assert::assertArrayHasKey(ShortUrlMetaInputFilter::SHORT_CODE_LENGTH, $parsedBody);
            Assert::assertEquals($expectedLength, $parsedBody[ShortUrlMetaInputFilter::SHORT_CODE_LENGTH]);

            return $req;
        }))->willReturn(new Response());

        $this->middleware->process($request, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }

    public function provideBodies(): iterable
    {
        yield 'value provided' => [[ShortUrlMetaInputFilter::SHORT_CODE_LENGTH => 6], 6];
        yield 'value not provided' => [[], 8];
    }
}
