<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Psr\Container;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\NoAuthenticationException;

interface AuthenticationPluginManagerInterface extends Container\ContainerInterface
{
    /**
     * @throws Container\ContainerExceptionInterface
     * @throws NoAuthenticationException
     */
    public function fromRequest(ServerRequestInterface $request): Plugin\AuthenticationPluginInterface;
}
