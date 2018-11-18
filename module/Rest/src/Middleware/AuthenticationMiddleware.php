<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPlugin;
use Shlinkio\Shlink\Rest\Authentication\RequestToHttpAuthPluginInterface;
use Shlinkio\Shlink\Rest\Exception\NoAuthenticationException;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;
use function Functional\contains;
use function implode;
use function sprintf;

class AuthenticationMiddleware implements MiddlewareInterface, StatusCodeInterface, RequestMethodInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $routesWhitelist;
    /**
     * @var RequestToHttpAuthPluginInterface
     */
    private $requestToAuthPlugin;

    public function __construct(
        RequestToHttpAuthPluginInterface $requestToAuthPlugin,
        array $routesWhitelist,
        LoggerInterface $logger = null
    ) {
        $this->routesWhitelist = $routesWhitelist;
        $this->requestToAuthPlugin = $requestToAuthPlugin;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult === null
            || $routeResult->isFailure()
            || $request->getMethod() === self::METHOD_OPTIONS
            || contains($this->routesWhitelist, $routeResult->getMatchedRouteName())
        ) {
            return $handler->handle($request);
        }

        try {
            $plugin = $this->requestToAuthPlugin->fromRequest($request);
        } catch (ContainerExceptionInterface | NoAuthenticationException $e) {
            $this->logger->warning('Invalid or no authentication provided. {e}', ['e' => $e]);
            return $this->createErrorResponse(sprintf(
                'Expected one of the following authentication headers, but none were provided, ["%s"]',
                implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)
            ));
        }

        try {
            $plugin->verify($request);
            $response = $handler->handle($request);
            return $plugin->update($request, $response);
        } catch (VerifyAuthenticationException $e) {
            $this->logger->warning('Authentication verification failed. {e}', ['e' => $e]);
            return $this->createErrorResponse($e->getPublicMessage(), $e->getErrorCode());
        }
    }

    private function createErrorResponse(
        string $message,
        string $errorCode = RestUtils::INVALID_AUTHORIZATION_ERROR
    ): JsonResponse {
        return new JsonResponse([
            'error' => $errorCode,
            'message' => $message,
        ], self::STATUS_UNAUTHORIZED);
    }
}
