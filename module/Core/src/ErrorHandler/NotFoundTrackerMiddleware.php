<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

class NotFoundTrackerMiddleware implements MiddlewareInterface
{
    public function __construct(private VisitsTrackerInterface $visitsTracker)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var NotFoundType $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);
        $visitor = Visitor::fromRequest($request);

        if ($notFoundType->isBaseUrl()) {
            $this->visitsTracker->trackBaseUrlVisit($visitor);
        } elseif ($notFoundType->isRegularNotFound()) {
            $this->visitsTracker->trackRegularNotFoundVisit($visitor);
        } elseif ($notFoundType->isInvalidShortUrl()) {
            $this->visitsTracker->trackInvalidShortUrlVisit($visitor);
        }

        return $handler->handle($request);
    }
}
