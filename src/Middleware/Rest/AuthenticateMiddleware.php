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

class AuthenticateMiddleware extends AbstractRestMiddleware
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
     * @param Request $request
     * @param Response $response
     * @param callable|null $out
     * @return null|Response
     */
    public function dispatch(Request $request, Response $response, callable $out = null)
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
