<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Rest\Authentication\JWTServiceInterface;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Stdlib\ErrorHandler;

class CheckAuthenticationMiddleware implements MiddlewareInterface, StatusCodeInterface
{
    public const AUTHORIZATION_HEADER = 'Authorization';

    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var JWTServiceInterface
     */
    private $jwtService;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $routesWhitelist;

    public function __construct(
        JWTServiceInterface $jwtService,
        TranslatorInterface $translator,
        array $routesWhitelist,
        LoggerInterface $logger = null
    ) {
        $this->translator = $translator;
        $this->jwtService = $jwtService;
        $this->routesWhitelist = $routesWhitelist;
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
     * @throws \ErrorException
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // If current route is the authenticate route or an OPTIONS request, continue to the next middleware
        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult === null
            || $routeResult->isFailure()
            || $request->getMethod() === 'OPTIONS'
            || \in_array($routeResult->getMatchedRouteName(), $this->routesWhitelist, true)
        ) {
            return $handler->handle($request);
        }

        // Check that the auth header was provided, and that it belongs to a non-expired token
        if (! $request->hasHeader(self::AUTHORIZATION_HEADER)) {
            return $this->createTokenErrorResponse();
        }

        // Get token making sure the an authorization type is provided
        $authToken = $request->getHeaderLine(self::AUTHORIZATION_HEADER);
        $authTokenParts = \explode(' ', $authToken);
        if (\count($authTokenParts) === 1) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_AUTHORIZATION_ERROR,
                'message' => \sprintf($this->translator->translate(
                    'You need to provide the Bearer type in the %s header.'
                ), self::AUTHORIZATION_HEADER),
            ], self::STATUS_UNAUTHORIZED);
        }

        // Make sure the authorization type is Bearer
        [$authType, $jwt] = $authTokenParts;
        if (\strtolower($authType) !== 'bearer') {
            return new JsonResponse([
                'error' => RestUtils::INVALID_AUTHORIZATION_ERROR,
                'message' => \sprintf($this->translator->translate(
                    'Provided authorization type %s is not supported. Use Bearer instead.'
                ), $authType),
            ], self::STATUS_UNAUTHORIZED);
        }

        try {
            ErrorHandler::start();
            if (! $this->jwtService->verify($jwt)) {
                return $this->createTokenErrorResponse();
            }
            ErrorHandler::stop(true);

            // Update the token expiration and continue to next middleware
            $jwt = $this->jwtService->refresh($jwt);
            $response = $handler->handle($request);

            // Return the response with the updated token on it
            return $response->withHeader(self::AUTHORIZATION_HEADER, 'Bearer ' . $jwt);
        } catch (AuthenticationException $e) {
            $this->logger->warning('Tried to access API with an invalid JWT.' . PHP_EOL . $e);
            return $this->createTokenErrorResponse();
        } finally {
            ErrorHandler::clean();
        }
    }

    /**
     * @return JsonResponse
     * @throws \InvalidArgumentException
     */
    private function createTokenErrorResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => RestUtils::INVALID_AUTH_TOKEN_ERROR,
            'message' => \sprintf(
                $this->translator->translate(
                    'Missing or invalid auth token provided. Perform a new authentication request and send provided '
                    . 'token on every new request on the "%s" header'
                ),
                self::AUTHORIZATION_HEADER
            ),
        ], self::STATUS_UNAUTHORIZED);
    }
}
