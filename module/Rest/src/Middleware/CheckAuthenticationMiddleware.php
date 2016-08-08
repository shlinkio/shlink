<?php
namespace Shlinkio\Shlink\Rest\Middleware;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Rest\Authentication\JWTService;
use Shlinkio\Shlink\Rest\Authentication\JWTServiceInterface;
use Shlinkio\Shlink\Rest\Exception\AuthenticationException;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Stratigility\MiddlewareInterface;

class CheckAuthenticationMiddleware implements MiddlewareInterface
{
    const AUTHORIZATION_HEADER = 'Authorization';

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
     * CheckAuthenticationMiddleware constructor.
     * @param JWTServiceInterface|JWTService $jwtService
     * @param TranslatorInterface $translator
     * @param LoggerInterface $logger
     *
     * @Inject({JWTService::class, "translator", "Logger_Shlink"})
     */
    public function __construct(
        JWTServiceInterface $jwtService,
        TranslatorInterface $translator,
        LoggerInterface $logger = null
    ) {
        $this->translator = $translator;
        $this->jwtService = $jwtService;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        // If current route is the authenticate route or an OPTIONS request, continue to the next middleware
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! isset($routeResult)
            || $routeResult->isFailure()
            || $routeResult->getMatchedRouteName() === 'rest-authenticate'
            || $request->getMethod() === 'OPTIONS'
        ) {
            return $out($request, $response);
        }

        // Check that the auth header was provided, and that it belongs to a non-expired token
        if (! $request->hasHeader(self::AUTHORIZATION_HEADER)) {
            return $this->createTokenErrorResponse();
        }

        // Get token making sure the an authorization type is provided
        $authToken = $request->getHeaderLine(self::AUTHORIZATION_HEADER);
        $authTokenParts = explode(' ', $authToken);
        if (count($authTokenParts) === 1) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_AUTHORIZATION_ERROR,
                'message' => sprintf($this->translator->translate(
                    'You need to provide the Bearer type in the %s header.'
                ), self::AUTHORIZATION_HEADER),
            ], 401);
        }

        // Make sure the authorization type is Bearer
        list($authType, $jwt) = $authTokenParts;
        if (strtolower($authType) !== 'bearer') {
            return new JsonResponse([
                'error' => RestUtils::INVALID_AUTHORIZATION_ERROR,
                'message' => sprintf($this->translator->translate(
                    'Provided authorization type %s is not supported. Use Bearer instead.'
                ), $authType),
            ], 401);
        }

        try {
            if (! $this->jwtService->verify($jwt)) {
                return $this->createTokenErrorResponse();
            }

            // Update the token expiration and continue to next middleware
            $jwt = $this->jwtService->refresh($jwt);
            /** @var Response $response */
            $response = $out($request, $response);

            // Return the response with the updated token on it
            return $response->withHeader(self::AUTHORIZATION_HEADER, 'Bearer ' . $jwt);
        } catch (AuthenticationException $e) {
            $this->logger->warning('Tried to access API with an invalid JWT.' . PHP_EOL . $e);
            return $this->createTokenErrorResponse();
        }
    }

    protected function createTokenErrorResponse()
    {
        return new JsonResponse([
            'error' => RestUtils::INVALID_AUTH_TOKEN_ERROR,
            'message' => sprintf(
                $this->translator->translate(
                    'Missing or invalid auth token provided. Perform a new authentication request and send provided '
                    . 'token on every new request on the "%s" header'
                ),
                self::AUTHORIZATION_HEADER
            ),
        ], 401);
    }
}
