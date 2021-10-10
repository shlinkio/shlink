<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit;

use Fig\Http\Message\RequestMethodInterface;
use InvalidArgumentException;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use PhpIP\IP;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Common\Middleware\IpAddressMiddlewareFactory;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;

use function explode;
use function Functional\map;
use function Functional\some;
use function implode;
use function str_contains;

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

        match (true) { // @phpstan-ignore-line
            $notFoundType?->isBaseUrl() => $this->visitsTracker->trackBaseUrlVisit($visitor),
            $notFoundType?->isRegularNotFound() => $this->visitsTracker->trackRegularNotFoundVisit($visitor),
            $notFoundType?->isInvalidShortUrl() => $this->visitsTracker->trackInvalidShortUrlVisit($visitor),
        };
    }

    private function shouldTrackRequest(ServerRequestInterface $request): bool
    {
        $forwardedMethod = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
        if ($forwardedMethod === self::METHOD_HEAD) {
            return false;
        }

        $remoteAddr = $request->getAttribute(IpAddressMiddlewareFactory::REQUEST_ATTR);
        if ($this->shouldDisableTrackingFromAddress($remoteAddr)) {
            return false;
        }

        $query = $request->getQueryParams();
        return ! $this->trackingOptions->queryHasDisableTrackParam($query);
    }

    private function shouldDisableTrackingFromAddress(?string $remoteAddr): bool
    {
        if ($remoteAddr === null || ! $this->trackingOptions->hasDisableTrackingFrom()) {
            return false;
        }

        try {
            $ip = IP::create($remoteAddr);
        } catch (InvalidArgumentException) {
            return false;
        }

        $remoteAddrParts = explode('.', $remoteAddr);
        $disableTrackingFrom = $this->trackingOptions->disableTrackingFrom();

        return some($disableTrackingFrom, function (string $value) use ($ip, $remoteAddrParts): bool {
            try {
                return match (true) {
                    str_contains($value, '*') => $ip->matches($this->parseValueWithWildcards($value, $remoteAddrParts)),
                    str_contains($value, '/') => $ip->isIn($value),
                    default => $ip->matches($value),
                };
            } catch (InvalidArgumentException) {
                return false;
            }
        });
    }

    private function parseValueWithWildcards(string $value, array $remoteAddrParts): string
    {
        // Replace wildcard parts with the corresponding ones from the remote address
        return implode('.', map(
            explode('.', $value),
            fn (string $part, int $index) => $part === '*' ? $remoteAddrParts[$index] : $part,
        ));
    }
}
