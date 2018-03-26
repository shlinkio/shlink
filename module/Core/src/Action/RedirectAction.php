<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Zend\Diactoros\Response\RedirectResponse;

class RedirectAction extends AbstractTrackingAction
{
    protected function createResp(string $longUrl): Response
    {
        // Return a redirect response to the long URL.
        // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
        return new RedirectResponse($longUrl);
    }
}
