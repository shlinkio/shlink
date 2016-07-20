<?php
namespace Shlinkio\Shlink\Rest\Middleware;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Exception\InvalidArgumentException;
use Shlinkio\Shlink\Rest\Service\RestTokenService;
use Shlinkio\Shlink\Rest\Service\RestTokenServiceInterface;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;
use Zend\Stratigility\MiddlewareInterface;

class CheckAuthenticationMiddleware implements MiddlewareInterface
{
    const AUTH_TOKEN_HEADER = 'X-Auth-Token';

    /**
     * @var RestTokenServiceInterface
     */
    private $restTokenService;

    /**
     * CheckAuthenticationMiddleware constructor.
     * @param RestTokenServiceInterface|RestTokenService $restTokenService
     *
     * @Inject({RestTokenService::class})
     */
    public function __construct(RestTokenServiceInterface $restTokenService)
    {
        $this->restTokenService = $restTokenService;
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
        if ((isset($routeResult) && $routeResult->getMatchedRouteName() === 'rest-authenticate')
            || $request->getMethod() === 'OPTIONS'
        ) {
            return $out($request, $response);
        }

        // Check that the auth header was provided, and that it belongs to a non-expired token
        if (! $request->hasHeader(self::AUTH_TOKEN_HEADER)) {
            return $this->createTokenErrorResponse();
        }

        $authToken = $request->getHeaderLine(self::AUTH_TOKEN_HEADER);
        try {
            $restToken = $this->restTokenService->getByToken($authToken);
            if ($restToken->isExpired()) {
                return $this->createTokenErrorResponse();
            }

            // Update the token expiration and continue to next middleware
            $this->restTokenService->updateExpiration($restToken);
            return $out($request, $response);
        } catch (InvalidArgumentException $e) {
            return $this->createTokenErrorResponse();
        }
    }

    protected function createTokenErrorResponse()
    {
        return new JsonResponse([
            'error' => RestUtils::INVALID_AUTH_TOKEN_ERROR,
            'message' => sprintf(
                'Missing or invalid auth token provided. Perform a new authentication request and send provided token '
                . 'on every new request on the "%s" header',
                self::AUTH_TOKEN_HEADER
            ),
        ], 401);
    }
}
