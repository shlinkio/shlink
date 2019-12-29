<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Middleware\BackwardsCompatibleProblemDetailsMiddleware;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Uri;

class BackwardsCompatibleProblemDetailsMiddlewareTest extends TestCase
{
    /** @var BackwardsCompatibleProblemDetailsMiddleware */
    private $middleware;
    /** @var ObjectProphecy */
    private $handler;

    public function setUp(): void
    {
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware = new BackwardsCompatibleProblemDetailsMiddleware([
            404 => 'NOT_FOUND',
            500 => 'INTERNAL_SERVER_ERROR',
        ], 0);
    }

    /**
     * @test
     * @dataProvider provideNonProcessableResponses
     */
    public function nonProblemDetailsOrInvalidResponsesAreReturnedAsTheyAre(Response $response): void
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response();
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
    }

    /**
     * @test
     * @dataProvider provideStatusAndTypes
     */
    public function properlyMapsTypesBasedOnResponseStatus(Response\JsonResponse $response, string $expectedType): void
    {
        $request = ServerRequestFactory::fromGlobals()->withUri(new Uri('/v2/something'));
        $handle = $this->handler->handle($request)->willReturn($response);

        /** @var Response\JsonResponse $result */
        $result = $this->middleware->process($request, $this->handler->reveal());
        $payload = $result->getPayload();

        $this->assertEquals($expectedType, $payload['type']);
        $this->assertArrayNotHasKey('error', $payload);
        $this->assertArrayNotHasKey('message', $payload);
        $handle->shouldHaveBeenCalledOnce();
    }

    public function provideStatusAndTypes(): iterable
    {
        yield [$this->jsonResponse(['type' => 'https://httpstatus.es/404'], 404), 'NOT_FOUND'];
        yield [$this->jsonResponse(['type' => 'https://httpstatus.es/500'], 500), 'INTERNAL_SERVER_ERROR'];
        yield [$this->jsonResponse(['type' => 'https://httpstatus.es/504'], 504), 'https://httpstatus.es/504'];
        yield [$this->jsonResponse(['type' => 'something_else'], 404), 'something_else'];
        yield [$this->jsonResponse(['type' => 'something_else'], 500), 'something_else'];
        yield [$this->jsonResponse(['type' => 'something_else'], 504), 'something_else'];
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
