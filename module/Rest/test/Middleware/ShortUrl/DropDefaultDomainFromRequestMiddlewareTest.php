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
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware;

class DropDefaultDomainFromRequestMiddlewareTest extends TestCase
{
    private DropDefaultDomainFromRequestMiddleware $middleware;
    private ObjectProphecy $next;

    public function setUp(): void
    {
        $this->next = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new DropDefaultDomainFromRequestMiddleware('doma.in');
    }

    /**
     * @test
     * @dataProvider provideQueryParams
     */
    public function domainIsDroppedWhenDefaultOneIsProvided(array $providedPayload, array $expectedPayload): void
    {
        $req = ServerRequestFactory::fromGlobals()->withQueryParams($providedPayload)->withParsedBody($providedPayload);

        $handle = $this->next->handle(Argument::that(function (ServerRequestInterface $request) use ($expectedPayload) {
            Assert::assertEquals($expectedPayload, $request->getQueryParams());
            Assert::assertEquals($expectedPayload, $request->getParsedBody());
            return $request;
        }))->willReturn(new Response());

        $this->middleware->process($req, $this->next->reveal());

        $handle->shouldHaveBeenCalledOnce();
    }

    public function provideQueryParams(): iterable
    {
        yield [[], []];
        yield [['foo' => 'bar'], ['foo' => 'bar']];
        yield [['foo' => 'bar', 'domain' => 'doma.in'], ['foo' => 'bar']];
        yield [['foo' => 'bar', 'domain' => 'not_default'], ['foo' => 'bar', 'domain' => 'not_default']];
        yield [['domain' => 'doma.in'], []];
    }
}
