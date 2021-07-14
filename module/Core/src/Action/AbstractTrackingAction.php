<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\TrackingOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

use function array_key_exists;

abstract class AbstractTrackingAction implements MiddlewareInterface, RequestMethodInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private VisitsTrackerInterface $visitTracker,
        private ShortUrlRedirectionBuilderInterface $redirectionBuilder,
        private TrackingOptions $trackingOptions,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
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

            return $this->createSuccessResp($this->redirectionBuilder->buildShortUrlRedirect($shortUrl, $query));
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while tracking short code. {e}', ['e' => $e]);
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

    abstract protected function createSuccessResp(string $longUrl): ResponseInterface;

    abstract protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;
}
