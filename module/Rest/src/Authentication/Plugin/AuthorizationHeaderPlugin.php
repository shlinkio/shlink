<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Authentication\JWTServiceInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;
use Shlinkio\Shlink\Rest\Util\RestUtils;
use Throwable;

use function count;
use function explode;
use function sprintf;
use function strtolower;

class AuthorizationHeaderPlugin implements AuthenticationPluginInterface
{
    public const HEADER_NAME = 'Authorization';

    /** @var JWTServiceInterface */
    private $jwtService;

    public function __construct(JWTServiceInterface $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * @throws VerifyAuthenticationException
     */
    public function verify(ServerRequestInterface $request): void
    {
        // Get token making sure the an authorization type is provided
        $authToken = $request->getHeaderLine(self::HEADER_NAME);
        $authTokenParts = explode(' ', $authToken);
        if (count($authTokenParts) === 1) {
            throw VerifyAuthenticationException::withError(
                RestUtils::INVALID_AUTHORIZATION_ERROR,
                sprintf('You need to provide the Bearer type in the %s header.', self::HEADER_NAME)
            );
        }

        // Make sure the authorization type is Bearer
        [$authType, $jwt] = $authTokenParts;
        if (strtolower($authType) !== 'bearer') {
            throw VerifyAuthenticationException::withError(
                RestUtils::INVALID_AUTHORIZATION_ERROR,
                sprintf('Provided authorization type %s is not supported. Use Bearer instead.', $authType)
            );
        }

        try {
            if (! $this->jwtService->verify($jwt)) {
                throw $this->createInvalidTokenError();
            }
        } catch (Throwable $e) {
            throw $this->createInvalidTokenError($e);
        }
    }

    private function createInvalidTokenError(?Throwable $prev = null): VerifyAuthenticationException
    {
        return VerifyAuthenticationException::withError(
            RestUtils::INVALID_AUTH_TOKEN_ERROR,
            sprintf(
                'Missing or invalid auth token provided. Perform a new authentication request and send provided '
                . 'token on every new request on the %s header',
                self::HEADER_NAME
            ),
            $prev
        );
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $authToken = $request->getHeaderLine(self::HEADER_NAME);
        [, $jwt] = explode(' ', $authToken);
        $jwt = $this->jwtService->refresh($jwt);

        return $response->withHeader(self::HEADER_NAME, sprintf('Bearer %s', $jwt));
    }
}
