<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware\ShortUrl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\ShortUrl\CreateShortUrlContentNegotiationMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\ServerRequest;

class CreateShortUrlContentNegotiationMiddlewareTest extends TestCase
{
    private CreateShortUrlContentNegotiationMiddleware $middleware;
    private ObjectProphecy $requestHandler;

    public function setUp(): void
    {
        $this->middleware = new CreateShortUrlContentNegotiationMiddleware();
        $this->requestHandler = $this->prophesize(RequestHandlerInterface::class);
    }

    /** @test */
    public function whenNoJsonResponseIsReturnedNoFurtherOperationsArePerformed(): void
    {
        $expectedResp = new Response();
        $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn($expectedResp);

        $resp = $this->middleware->process(new ServerRequest(), $this->requestHandler->reveal());

        $this->assertSame($expectedResp, $resp);
    }

    /**
     * @test
     * @dataProvider provideData
     * @param array $query
     */
    public function properResponseIsReturned(?string $accept, array $query, string $expectedContentType): void
    {
        $request = (new ServerRequest())->withQueryParams($query);
        if ($accept !== null) {
            $request = $request->withHeader('Accept', $accept);
        }

        $handle = $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn(
            new JsonResponse(['shortUrl' => 'http://doma.in/foo']),
        );

        $response = $this->middleware->process($request, $this->requestHandler->reveal());

        $this->assertEquals($expectedContentType, $response->getHeaderLine('Content-type'));
        $handle->shouldHaveBeenCalled();
    }

    public function provideData(): iterable
    {
        yield [null, [], 'application/json'];
        yield [null, ['format' => 'json'], 'application/json'];
        yield [null, ['format' => 'invalid'], 'application/json'];
        yield [null, ['format' => 'txt'], 'text/plain'];
        yield ['application/json', [], 'application/json'];
        yield ['application/xml', [], 'application/json'];
        yield ['text/plain', [], 'text/plain'];
        yield ['application/json', ['format' => 'txt'], 'text/plain'];
    }

    /**
     * @test
     * @dataProvider provideTextBodies
     * @param array $json
     */
    public function properBodyIsReturnedInPlainTextResponses(array $json, string $expectedBody): void
    {
        $request = (new ServerRequest())->withQueryParams(['format' => 'txt']);

        $handle = $this->requestHandler->handle(Argument::type(ServerRequestInterface::class))->willReturn(
            new JsonResponse($json),
        );

        $response = $this->middleware->process($request, $this->requestHandler->reveal());

        $this->assertEquals($expectedBody, (string) $response->getBody());
        $handle->shouldHaveBeenCalled();
    }

    public function provideTextBodies(): iterable
    {
        yield 'shortUrl key' => [['shortUrl' => 'foobar'], 'foobar'];
        yield 'error key' => [['error' => 'FOO_BAR'], 'FOO_BAR'];
        yield 'no shortUrl or error keys' => [[], ''];
    }
}
