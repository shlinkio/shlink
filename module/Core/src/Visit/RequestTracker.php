<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\Options\TrackingOptions;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Exception\InvalidIpFormatException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Util\IpAddressUtils;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

use function Shlinkio\Shlink\Core\ipAddressFromRequest;

readonly class RequestTracker implements RequestTrackerInterface, RequestMethodInterface
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

        match (true) {
            $notFoundType?->isBaseUrl() => $this->visitsTracker->trackBaseUrlVisit($visitor),
            $notFoundType?->isRegularNotFound() => $this->visitsTracker->trackRegularNotFoundVisit($visitor),
            $notFoundType?->isInvalidShortUrl() => $this->visitsTracker->trackInvalidShortUrlVisit($visitor),
            default => null,
        };
    }

    private function shouldTrackRequest(ServerRequestInterface $request): bool
    {
        $forwardedMethod = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
        if ($forwardedMethod === self::METHOD_HEAD) {
            return false;
        }

        $remoteAddr = ipAddressFromRequest($request);
        if ($this->shouldDisableTrackingFromAddress($remoteAddr)) {
            return false;
        }

        $query = $request->getQueryParams();
        return ! $this->trackingOptions->queryHasDisableTrackParam($query);
    }

    private function shouldDisableTrackingFromAddress(string|null $remoteAddr): bool
    {
        if ($remoteAddr === null || ! $this->trackingOptions->hasDisableTrackingFrom()) {
            return false;
        }

        try {
            return IpAddressUtils::ipAddressMatchesGroups($remoteAddr, $this->trackingOptions->disableTrackingFrom);
        } catch (InvalidIpFormatException) {
            return false;
        }
    }
}
