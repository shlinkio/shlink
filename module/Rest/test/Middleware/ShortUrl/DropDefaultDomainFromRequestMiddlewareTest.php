<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\DropDefaultDomainFromRequestMiddleware;

class DropDefaultDomainFromRequestMiddlewareTest extends TestCase
{
    private DropDefaultDomainFromRequestMiddleware $middleware;
    private MockObject & RequestHandlerInterface $next;

    protected function setUp(): void
    {
        $this->next = $this->createMock(RequestHandlerInterface::class);
        $this->middleware = new DropDefaultDomainFromRequestMiddleware('s.test');
    }

    /**
     * @test
     * @dataProvider provideQueryParams
     */
    public function domainIsDroppedWhenDefaultOneIsProvided(array $providedPayload, array $expectedPayload): void
    {
        $req = ServerRequestFactory::fromGlobals()->withQueryParams($providedPayload)->withParsedBody($providedPayload);

        $this->next->expects($this->once())->method('handle')->with($this->callback(
            function (ServerRequestInterface $request) use ($expectedPayload) {
                Assert::assertEquals($expectedPayload, $request->getQueryParams());
                Assert::assertEquals($expectedPayload, $request->getParsedBody());
                return true;
            },
        ))->willReturn(new Response());

        $this->middleware->process($req, $this->next);
    }

    public function provideQueryParams(): iterable
    {
        yield [[], []];
        yield [['foo' => 'bar'], ['foo' => 'bar']];
        yield [['foo' => 'bar', 'domain' => 's.test'], ['foo' => 'bar']];
        yield [['foo' => 'bar', 'domain' => 'not_default'], ['foo' => 'bar', 'domain' => 'not_default']];
        yield [['domain' => 's.test'], []];
    }
}
