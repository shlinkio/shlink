<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\Rest\Middleware;

use Exception;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Action\AuthenticateAction;
use Shlinkio\Shlink\Rest\Authentication\Plugin\AuthenticationPluginInterface;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPlugin;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPluginInterface;
use Shlinkio\Shlink\Rest\Exception\NoAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouteResult;
use function implode;
use function sprintf;
use function Zend\Stratigility\middleware;

class AuthenticationMiddlewareTest extends TestCase
{
    /** @var AuthenticationMiddleware */
    private $middleware;
    /** @var ObjectProphecy */
    private $requestToPlugin;

    /** @var callable */
    private $dummyMiddleware;

    public function setUp(): void
    {
        $this->requestToPlugin = $this->prophesize(RequestToHttpAuthPluginInterface::class);
        $this->middleware = new AuthenticationMiddleware($this->requestToPlugin->reveal(), [AuthenticateAction::class]);
    }

    /**
     * @test
     * @dataProvider provideWhitelistedRequests
     */
    public function someWhiteListedSituationsFallbackToNextMiddleware(ServerRequestInterface $request)
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle($request)->willReturn(new Response());
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willReturn(
            $this->prophesize(AuthenticationPluginInterface::class)->reveal()
        );

        $this->middleware->process($request, $handler->reveal());

        $handle->shouldHaveBeenCalledOnce();
        $fromRequest->shouldNotHaveBeenCalled();
    }

    public function provideWhitelistedRequests(): array
    {
        $dummyMiddleware = $this->getDummyMiddleware();

        return [
            'with no route result' => [new ServerRequest()],
            'with failure route result' => [(new ServerRequest())->withAttribute(
                RouteResult::class,
                RouteResult::fromRouteFailure([RequestMethodInterface::METHOD_GET])
            )],
            'with whitelisted route' => [(new ServerRequest())->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(
                    new Route('foo', $dummyMiddleware, Route::HTTP_METHOD_ANY, AuthenticateAction::class)
                )
            )],
            'with OPTIONS method' => [(new ServerRequest())->withAttribute(
                RouteResult::class,
                RouteResult::fromRoute(new Route('bar', $dummyMiddleware), [])
            )->withMethod(RequestMethodInterface::METHOD_OPTIONS)],
        ];
    }

    /**
     * @test
     * @dataProvider provideExceptions
     */
    public function errorIsReturnedWhenNoValidAuthIsProvided($e)
    {
        $request = (new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), [])
        );
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willThrow($e);

        /** @var Response\JsonResponse $response */
        $response = $this->middleware->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());
        $payload = $response->getPayload();

        $this->assertEquals(RestUtils::INVALID_AUTHORIZATION_ERROR, $payload['error']);
        $this->assertEquals(sprintf(
            'Expected one of the following authentication headers, but none were provided, ["%s"]',
            implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
        ), $payload['message']);
        $fromRequest->shouldHaveBeenCalledOnce();
    }

    public function provideExceptions(): array
    {
        return [
            [new class extends Exception implements ContainerExceptionInterface {
            }],
            [NoAuthenticationException::fromExpectedTypes([])],
        ];
    }

    /**
     * @test
     */
    public function errorIsReturnedWhenVerificationFails()
    {
        $request = (new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), [])
        );
        $plugin = $this->prophesize(AuthenticationPluginInterface::class);

        $verify = $plugin->verify($request)->willThrow(
            VerifyAuthenticationException::withError('the_error', 'the_message')
        );
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willReturn($plugin->reveal());

        /** @var Response\JsonResponse $response */
        $response = $this->middleware->process($request, $this->prophesize(RequestHandlerInterface::class)->reveal());
        $payload = $response->getPayload();

        $this->assertEquals('the_error', $payload['error']);
        $this->assertEquals('the_message', $payload['message']);
        $verify->shouldHaveBeenCalledOnce();
        $fromRequest->shouldHaveBeenCalledOnce();
    }

    /**
     * @test
     */
    public function updatedResponseIsReturnedWhenVerificationPasses()
    {
        $newResponse = new Response();
        $request = (new ServerRequest())->withAttribute(
            RouteResult::class,
            RouteResult::fromRoute(new Route('bar', $this->getDummyMiddleware()), [])
        );
        $plugin = $this->prophesize(AuthenticationPluginInterface::class);

        $verify = $plugin->verify($request)->will(function () {
        });
        $update = $plugin->update($request, Argument::type(ResponseInterface::class))->willReturn($newResponse);
        $fromRequest = $this->requestToPlugin->fromRequest(Argument::any())->willReturn($plugin->reveal());

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handle = $handler->handle($request)->willReturn(new Response());
        $response = $this->middleware->process($request, $handler->reveal());

        $this->assertSame($response, $newResponse);
        $verify->shouldHaveBeenCalledOnce();
        $update->shouldHaveBeenCalledOnce();
        $handle->shouldHaveBeenCalledOnce();
        $fromRequest->shouldHaveBeenCalledOnce();
    }

    private function getDummyMiddleware(): MiddlewareInterface
    {
        return middleware(function () {
            return new Response\EmptyResponse();
        });
    }
}
