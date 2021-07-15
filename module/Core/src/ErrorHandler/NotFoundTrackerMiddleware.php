<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

class NotFoundTrackerMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestTrackerInterface $requestTracker)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->requestTracker->trackNotFoundIfApplicable($request);
        return $handler->handle($request);
    }
}
