<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Action\HealthAction;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;

use function Laminas\Stratigility\middleware;

class AuthenticationMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    private AuthenticationMiddleware $middleware;
    private ObjectProphecy $apiKeyService;
    private ObjectProphecy $handler;

    public function setUp(): void
    {
        $this->apiKeyService = $this->prophesize(ApiKeyServiceInterface::class);
        $this->middleware = new AuthenticationMiddleware($this->apiKeyService->reveal(), [HealthAction::class]);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideWhitelistedRequests
     */
    public function someWhiteListedSituationsFallbackToNextMiddleware(ServerRequestInterface $request): void
    {
        $handle = $this->handler->handle($request)->willReturn(new Response());
        $checkApiKey = $this->apiKeyService->check(Argument::any());

        $this->middleware->process($request, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $checkApiKey->shouldNotHaveBeenCalled();
    }

    public function provideWhitelistedRequests(): iterable
    {
        $dummyMiddleware = $this->getDummyMiddleware();

        yield 'with no route result' => [new ServerRequest()];
        yield 'with failure route result' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteFailure([RequestMethodInterface::METHOD_GET]),
        )];
        yield 'with whitelisted route' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(
                new Route('foo', $dummyMiddleware, Route::HTTP_METHOD_ANY, HealthAction::class),
            ),
        )];
        yield 'with OPTIONS method' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $dummyMiddleware), []),
        )->withMethod(RequestMethodInterface::METHOD_OPTIONS)];
    }

    /**
     * @test
     * @dataProvider provideRequestsWithoutApiKey
     */
    public function throwsExceptionWhenNoApiKeyIsProvided(ServerRequestInterface $request): void
    {
        $this->apiKeyService->check(Argument::any())->shouldNotBeCalled();
        $this->handler->handle($request)->shouldNotBeCalled();
        $this->expectException(MissingAuthenticationException::class);
        $this->expectExceptionMessage(
            'Expected one of the following authentication headers, ["X-Api-Key"], but none were provided',
        );

        $this->middleware->process($request, $this->handler->reveal());
    }

    public function provideRequestsWithoutApiKey(): iterable
    {
        $baseRequest = ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), []),
        );

        yield 'no api key' => [$baseRequest];
        yield 'empty api key' => [$baseRequest->withHeader('X-Api-Key', '')];
    }

    /** @test */
    public function throwsExceptionWhenProvidedApiKeyIsInvalid(): void
    {
        $apiKey = 'abc123';
        $request = ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), []),
            )
            ->withHeader('X-Api-Key', $apiKey);

        $this->apiKeyService->check($apiKey)->willReturn(false)->shouldBeCalledOnce();
        $this->handler->handle($request)->shouldNotBeCalled();
        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage('Provided API key does not exist or is invalid');

        $this->middleware->process($request, $this->handler->reveal());
    }

    /** @test */
    public function validApiKeyFallsBackToNextMiddleware(): void
    {
        $apiKey = 'abc123';
        $request = ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), []),
            )
            ->withHeader('X-Api-Key', $apiKey);

        $handle = $this->handler->handle($request)->willReturn(new Response());
        $checkApiKey = $this->apiKeyService->check($apiKey)->willReturn(true);

        $this->middleware->process($request, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $checkApiKey->shouldHaveBeenCalledOnce();
    }

    private function getDummyMiddleware(): MiddlewareInterface
    {
        return middleware(fn () => new Response\EmptyResponse());
    }
}
