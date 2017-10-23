<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action\Util;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Response\NotFoundDelegate;

trait ErrorResponseBuilderTrait
{
    private function buildErrorResponse(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $request = $request->withAttribute(NotFoundDelegate::NOT_FOUND_TEMPLATE, 'ShlinkCore::invalid-short-code');
        return $delegate->process($request);
    }
}
