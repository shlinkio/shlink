<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

readonly class NotFoundTrackerMiddleware implements MiddlewareInterface
{
    public function __construct(private RequestTrackerInterface $requestTracker)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $this->requestTracker->trackNotFoundIfApplicable($request->withAttribute(
            REDIRECT_URL_REQUEST_ATTRIBUTE,
            $response->hasHeader('Location') ? $response->getHeaderLine('Location') : null,
        ));

        return $response;
    }
}
