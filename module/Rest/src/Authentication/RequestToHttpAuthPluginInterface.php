<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Rest\Exception\MissingAuthenticationException;

interface RequestToHttpAuthPluginInterface
{
    /**
     * @throws MissingAuthenticationException
     */
    public function fromRequest(ServerRequestInterface $request): Plugin\AuthenticationPluginInterface;
}
