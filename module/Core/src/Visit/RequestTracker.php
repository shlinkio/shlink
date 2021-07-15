<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;

use function array_key_exists;

class RequestTracker implements RequestTrackerInterface, RequestMethodInterface
{
    public function __construct(private VisitsTrackerInterface $visitsTracker, private TrackingOptions $trackingOptions)
    {
    }

    public function trackIfApplicable(ShortUrl $shortUrl, ServerRequestInterface $request): void
    {
        if ($this->shouldTrackRequest($request)) {
            $this->visitsTracker->track($shortUrl, Visitor::fromRequest($request));
        }
    }

    public function trackNotFoundIfApplicable(ServerRequestInterface $request): void
    {
        if (! $this->shouldTrackRequest($request)) {
            return;
        }

        /** @var NotFoundType|null $notFoundType */
        $notFoundType = $request->getAttribute(NotFoundType::class);
        $visitor = Visitor::fromRequest($request);

        if ($notFoundType?->isBaseUrl()) {
            $this->visitsTracker->trackBaseUrlVisit($visitor);
        } elseif ($notFoundType?->isRegularNotFound()) {
            $this->visitsTracker->trackRegularNotFoundVisit($visitor);
        } elseif ($notFoundType?->isInvalidShortUrl()) {
            $this->visitsTracker->trackInvalidShortUrlVisit($visitor);
        }
    }

    private function shouldTrackRequest(ServerRequestInterface $request): bool
    {
        $query = $request->getQueryParams();
        $disableTrackParam = $this->trackingOptions->getDisableTrackParam();
        $forwardedMethod = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
        if ($forwardedMethod === self::METHOD_HEAD) {
            return false;
        }

        return $disableTrackParam === null || ! array_key_exists($disableTrackParam, $query);
    }
}
