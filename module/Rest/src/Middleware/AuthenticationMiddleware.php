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
use Zend\I18n\Translator\TranslatorInterface;
use function implode;
use function Shlinkio\Shlink\Common\contains;
use function sprintf;

class AuthenticationMiddleware implements MiddlewareInterface, StatusCodeInterface, RequestMethodInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
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
        TranslatorInterface $translator,
        array $routesWhitelist,
        LoggerInterface $logger = null
    ) {
        $this->translator = $translator;
        $this->routesWhitelist = $routesWhitelist;
        $this->logger = $logger ?: new NullLogger();
        $this->requestToAuthPlugin = $requestToAuthPlugin;
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
            || contains($routeResult->getMatchedRouteName(), $this->routesWhitelist)
        ) {
            return $handler->handle($request);
        }

        try {
            $plugin = $this->requestToAuthPlugin->fromRequest($request);
        } catch (ContainerExceptionInterface | NoAuthenticationException $e) {
            $this->logger->warning('Invalid or no authentication provided. {e}', ['e' => $e]);
            return $this->createErrorResponse(sprintf($this->translator->translate(
                'Expected one of the following authentication headers, but none were provided, ["%s"]'
            ), implode('", "', RequestToHttpAuthPlugin::SUPPORTED_AUTH_HEADERS)));
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
