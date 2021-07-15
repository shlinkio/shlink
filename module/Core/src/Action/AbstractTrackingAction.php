<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

use function array_key_exists;

abstract class AbstractTrackingAction implements MiddlewareInterface, RequestMethodInterface
{
    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private VisitsTrackerInterface $visitTracker,
        private TrackingOptions $trackingOptions,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);
        $query = $request->getQueryParams();

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);

            if ($this->shouldTrackRequest($request, $query)) {
                $this->visitTracker->track($shortUrl, Visitor::fromRequest($request));
            }

            return $this->createSuccessResp($shortUrl, $request);
        } catch (ShortUrlNotFoundException) {
            return $this->createErrorResp($request, $handler);
        }
    }

    private function shouldTrackRequest(ServerRequestInterface $request, array $query): bool
    {
        $disableTrackParam = $this->trackingOptions->getDisableTrackParam();
        $forwardedMethod = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
        if ($forwardedMethod === self::METHOD_HEAD) {
            return false;
        }

        return $disableTrackParam === null || ! array_key_exists($disableTrackParam, $query);
    }

    abstract protected function createSuccessResp(
        ShortUrl $shortUrl,
        ServerRequestInterface $request,
    ): ResponseInterface;

    protected function createErrorResp(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
