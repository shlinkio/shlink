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
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Service\ApiKeyCheckResult;
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
        $this->middleware = new AuthenticationMiddleware(
            $this->apiKeyService->reveal(),
            [HealthAction::class],
            ['with_query_api_key'],
        );
        $this->handler = $this->prophesize(RequestHandlerInterface::class);
    }

    /**
     * @test
     * @dataProvider provideRequestsWithoutAuth
     */
    public function someSituationsFallbackToNextMiddleware(ServerRequestInterface $request): void
    {
        $handle = $this->handler->handle($request)->willReturn(new Response());
        $checkApiKey = $this->apiKeyService->check(Argument::any());

        $this->middleware->process($request, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $checkApiKey->shouldNotHaveBeenCalled();
    }

    public function provideRequestsWithoutAuth(): iterable
    {
        $dummyMiddleware = $this->getDummyMiddleware();

        yield 'no route result' => [new ServerRequest()];
        yield 'failure route result' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRouteFailure([RequestMethodInterface::METHOD_GET]),
        )];
        yield 'route without API key required' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(
                new Route('foo', $dummyMiddleware, Route::HTTP_METHOD_ANY, HealthAction::class),
            ),
        )];
        yield 'OPTIONS method' => [(new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $dummyMiddleware), []),
        )->withMethod(RequestMethodInterface::METHOD_OPTIONS)];
    }

    /**
     * @test
     * @dataProvider provideRequestsWithoutApiKey
     */
    public function throwsExceptionWhenNoApiKeyIsProvided(
        ServerRequestInterface $request,
        string $expectedMessage,
    ): void {
        $this->apiKeyService->check(Argument::any())->shouldNotBeCalled();
        $this->handler->handle($request)->shouldNotBeCalled();
        $this->expectException(MissingAuthenticationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->middleware->process($request, $this->handler->reveal());
    }

    public function provideRequestsWithoutApiKey(): iterable
    {
        $baseRequest = fn (string $routeName) => ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route($routeName, $this->getDummyMiddleware()), []),
        );
        $apiKeyMessage = 'Expected one of the following authentication headers, ["X-Api-Key"], but none were provided';
        $queryMessage = 'Expected authentication to be provided in "apiKey" query param';

        yield 'no api key in header' => [$baseRequest('bar'), $apiKeyMessage];
        yield 'empty api key in header' => [$baseRequest('bar')->withHeader('X-Api-Key', ''), $apiKeyMessage];
        yield 'no api key in query' => [$baseRequest('with_query_api_key'), $queryMessage];
        yield 'empty api key in query' => [
            $baseRequest('with_query_api_key')->withQueryParams(['apiKey' => '']),
            $queryMessage,
        ];
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

        $this->apiKeyService->check($apiKey)->willReturn(new ApiKeyCheckResult())->shouldBeCalledOnce();
        $this->handler->handle($request)->shouldNotBeCalled();
        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage('Provided API key does not exist or is invalid');

        $this->middleware->process($request, $this->handler->reveal());
    }

    /** @test */
    public function validApiKeyFallsBackToNextMiddleware(): void
    {
        $apiKey = ApiKey::create();
        $key = $apiKey->toString();
        $request = ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), []),
            )
            ->withHeader('X-Api-Key', $key);

        $handle = $this->handler->handle($request->withAttribute(ApiKey::class, $apiKey))->willReturn(new Response());
        $checkApiKey = $this->apiKeyService->check($key)->willReturn(new ApiKeyCheckResult($apiKey));

        $this->middleware->process($request, $this->handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $checkApiKey->shouldHaveBeenCalledOnce();
    }

    private function getDummyMiddleware(): MiddlewareInterface
    {
        return middleware(fn () => new Response\EmptyResponse());
    }
}
