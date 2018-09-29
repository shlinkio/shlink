<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication\Plugin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\VerifyAuthenticationException;

interface AuthenticationPluginInterface
{
    /**
     * @throws VerifyAuthenticationException
     */
    public function verify(ServerRequestInterface $request): void;

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
