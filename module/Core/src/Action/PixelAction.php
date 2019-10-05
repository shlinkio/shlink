<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Common\Response\PixelResponse;

class PixelAction extends AbstractTrackingAction
{
    protected function createSuccessResp(string $longUrl): ResponseInterface
    {
        return new PixelResponse();
    }

    protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return new PixelResponse();
    }
}
