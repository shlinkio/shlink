<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\BackwardsCompatibleProblemDetailsMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class BackwardsCompatibleProblemDetailsMiddlewareTest extends TestCase
{
    private BackwardsCompatibleProblemDetailsMiddleware $middleware;
    private ObjectProphecy $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new BackwardsCompatibleProblemDetailsMiddleware(0);
    }

    /**
     * @test
     * @dataProvider provideNonProcessableResponses
     */
    public function nonProblemDetailsOrInvalidResponsesAreReturnedAsTheyAre(
        Response $response,
        ?ServerRequest $request = null
    ): void {
        $request = $request ?? ServerRequestFactory::fromGlobals();
        $handle = $this->handler->handle($request)->willReturn($response);

        $result = $this->middleware->process($request, $this->handler->reveal());

        $this->assertSame($response, $result);
        $handle->shouldHaveBeenCalledOnce();
    }

    public function provideNonProcessableResponses(): iterable
    {
        yield 'no problem details' => [new Response()];
        yield 'invalid JSON' => [(new Response('data://text/plain,{invalid-json'))->withHeader(
            'Content-Type',
            'application/problem+json'
        )];
        yield 'version 2' => [
            (new Response())->withHeader('Content-type', 'application/problem+json'),
            ServerRequestFactory::fromGlobals()->withUri(new Uri('/v2/something')),
        ];
    }

    /**
     * @test
     * @dataProvider provideRequestPath
     */
    public function mapsDeprecatedPropertiesWhenRequestIsPerformedToVersionOne(string $requestPath): void
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri($requestPath));
        $response = $this->jsonResponse([
            'type' => 'the_type',
            'detail' => 'the_detail',
        ]);
        $handle = $this->handler->handle($request)->willReturn($response);

        /** @var Response\JsonResponse $result */
        $result = $this->middleware->process($request, $this->handler->reveal());
        $payload = $result->getPayload();

        $this->assertEquals([
            'type' => 'the_type',
            'detail' => 'the_detail',
            'error' => 'the_type',
            'message' => 'the_detail',
        ], $payload);
        $handle->shouldHaveBeenCalledOnce();
    }

    public function provideRequestPath(): iterable
    {
        yield 'no version' => ['/foo'];
        yield 'version one' => ['/v1/foo'];
    }

    private function jsonResponse(array $payload, int $status = 200): Response\JsonResponse
    {
        $headers = ['Content-Type' => 'application/problem+json'];
        return new Response\JsonResponse($payload, $status, $headers);
    }
}
