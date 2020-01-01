<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;

class EmptyResponseImplicitOptionsMiddlewareFactory
{
    public function __invoke(): ImplicitOptionsMiddleware
    {
        return new ImplicitOptionsMiddleware(fn () => new EmptyResponse());
    }
}
