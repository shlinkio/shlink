<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RedirectAction extends AbstractTrackingAction
{
    protected function createSuccessResp(string $longUrl): Response
    {
        // Return a redirect response to the long URL.
        // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
        return new RedirectResponse($longUrl);
    }

    protected function createErrorResp(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
