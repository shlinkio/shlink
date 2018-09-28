<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;

class ApiKeyHeaderPlugin implements AuthenticationPluginInterface
{
    public const HEADER_NAME = 'X-Api-Key';

    /**
     * @throws VerifyAuthenticationException
     */
    public function verify(ServerRequestInterface $request): void
    {
        // TODO: Implement check() method.
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
