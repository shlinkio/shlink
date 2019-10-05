<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action\Util;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Response\NotFoundHandler;

trait ErrorResponseBuilderTrait
{
    private function buildErrorResponse(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $request = $request->withAttribute(NotFoundHandler::NOT_FOUND_TEMPLATE, 'ShlinkCore::invalid-short-code');
        return $handler->handle($request);
    }
}
