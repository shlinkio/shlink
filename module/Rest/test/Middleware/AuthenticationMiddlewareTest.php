<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
    private AuthenticationMiddleware $middleware;
    private MockObject & ApiKeyServiceInterface $apiKeyService;
    private MockObject & RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->middleware = new AuthenticationMiddleware(
            $this->apiKeyService,
            [HealthAction::class],
            ['with_query_api_key'],
        );
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    #[Test, DataProvider('provideRequestsWithoutAuth')]
    public function someSituationsFallbackToNextMiddleware(ServerRequestInterface $request): void
    {
        $this->handler->expects($this->once())->method('handle')->with($request)->willReturn(new Response());
        $this->apiKeyService->expects($this->never())->method('check');

        $this->middleware->process($request, $this->handler);
    }

    public static function provideRequestsWithoutAuth(): iterable
    {
        $dummyMiddleware = self::getDummyMiddleware();

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

    #[Test, DataProvider('provideRequestsWithoutApiKey')]
    public function throwsExceptionWhenNoApiKeyIsProvided(
        ServerRequestInterface $request,
        string $expectedMessage,
    ): void {
        $this->apiKeyService->expects($this->never())->method('check');
        $this->handler->expects($this->never())->method('handle');
        $this->expectException(MissingAuthenticationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->middleware->process($request, $this->handler);
    }

    public static function provideRequestsWithoutApiKey(): iterable
    {
        $baseRequest = fn (string $routeName) => ServerRequestFactory::fromGlobals()->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route($routeName, self::getDummyMiddleware())), // @phpstan-ignore-line
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

    #[Test]
    public function throwsExceptionWhenProvidedApiKeyIsInvalid(): void
    {
        $apiKey = 'abc123';
        $request = ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', self::getDummyMiddleware()), []),
            )
            ->withHeader('X-Api-Key', $apiKey);

        $this->apiKeyService->expects($this->once())->method('check')->with($apiKey)->willReturn(
            new ApiKeyCheckResult(),
        );
        $this->handler->expects($this->never())->method('handle');
        $this->expectException(VerifyAuthenticationException::class);
        $this->expectExceptionMessage('Provided API key does not exist or is invalid');

        $this->middleware->process($request, $this->handler);
    }

    #[Test]
    public function validApiKeyFallsBackToNextMiddleware(): void
    {
        $apiKey = ApiKey::create();
        $key = $apiKey->toString();
        $request = ServerRequestFactory::fromGlobals()
            ->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', self::getDummyMiddleware()), []),
            )
            ->withHeader('X-Api-Key', $key);

        $this->handler->expects($this->once())->method('handle')->with(
            $request->withAttribute(ApiKey::class, $apiKey),
        )->willReturn(new Response());
        $this->apiKeyService->expects($this->once())->method('check')->with($key)->willReturn(
            new ApiKeyCheckResult($apiKey),
        );

        $this->middleware->process($request, $this->handler);
    }

    private static function getDummyMiddleware(): MiddlewareInterface
    {
        return middleware(fn () => new Response\EmptyResponse());
    }
}
