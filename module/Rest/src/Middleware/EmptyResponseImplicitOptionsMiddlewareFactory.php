<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class EmptyResponseImplicitOptionsMiddlewareFactory
{
    public function __invoke(): ImplicitOptionsMiddleware
    {
        return new ImplicitOptionsMiddleware(new class implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return new EmptyResponse();
            }
        });
    }
}
