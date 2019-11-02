<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Options;
use Zend\Diactoros\Response\RedirectResponse;

class RedirectAction extends AbstractTrackingAction
{
    /** @var Options\NotFoundRedirectOptions */
    private $redirectOptions;

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
