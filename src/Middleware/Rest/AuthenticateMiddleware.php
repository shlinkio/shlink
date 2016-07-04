<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Exception\AuthenticationException;
use Acelaya\UrlShortener\Service\RestTokenService;
use Acelaya\UrlShortener\Service\RestTokenServiceInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateMiddleware implements MiddlewareInterface
{
    /**
     * @var RestTokenServiceInterface
     */
    private $restTokenService;

    /**
     * AuthenticateMiddleware constructor.
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
        $authData = $request->getParsedBody();
        if (! isset($authData['username'], $authData['password'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => 'You have to provide both "username" and "password"'
            ], 400);
        }

        try {
            $token = $this->restTokenService->createToken($authData['username'], $authData['password']);
            return new JsonResponse(['token' => $token->getToken()]);
        } catch (AuthenticationException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => 'Invalid username and/or password',
            ], 401);
        }
    }
}
