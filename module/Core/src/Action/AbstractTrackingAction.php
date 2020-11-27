<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Query;
use League\Uri\Uri;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;

use function array_key_exists;
use function array_merge;

abstract class AbstractTrackingAction implements MiddlewareInterface, RequestMethodInterface
{
    private ShortUrlResolverInterface $urlResolver;
    private VisitsTrackerInterface $visitTracker;
    private AppOptions $appOptions;
    private LoggerInterface $logger;

    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        VisitsTrackerInterface $visitTracker,
        AppOptions $appOptions,
        ?LoggerInterface $logger = null
    ) {
        $this->urlResolver = $urlResolver;
        $this->visitTracker = $visitTracker;
        $this->appOptions = $appOptions;
        $this->logger = $logger ?? new NullLogger();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);
        $query = $request->getQueryParams();
        $disableTrackParam = $this->appOptions->getDisableTrackParam();

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);

            if ($this->shouldTrackRequest($request, $query, $disableTrackParam)) {
                $this->visitTracker->track($shortUrl, Visitor::fromRequest($request));
            }

            return $this->createSuccessResp($this->buildUrlToRedirectTo($shortUrl, $query, $disableTrackParam));
        } catch (ShortUrlNotFoundException $e) {
            $this->logger->warning('An error occurred while tracking short code. {e}', ['e' => $e]);
            return $this->createErrorResp($request, $handler);
        }
    }

    private function buildUrlToRedirectTo(ShortUrl $shortUrl, array $currentQuery, ?string $disableTrackParam): string
    {
        $uri = Uri::createFromString($shortUrl->getLongUrl());
        $hardcodedQuery = Query::parse($uri->getQuery() ?? '');
        if ($disableTrackParam !== null) {
            unset($currentQuery[$disableTrackParam]);
        }
        $mergedQuery = array_merge($hardcodedQuery, $currentQuery);

        return (string) (empty($mergedQuery) ? $uri : $uri->withQuery(Query::build($mergedQuery)));
    }

    private function shouldTrackRequest(ServerRequestInterface $request, array $query, ?string $disableTrackParam): bool
    {
        $forwardedMethod = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
        if ($forwardedMethod === self::METHOD_HEAD) {
            return false;
        }

        return $disableTrackParam === null || ! array_key_exists($disableTrackParam, $query);
    }

    abstract protected function createSuccessResp(string $longUrl): ResponseInterface;

    abstract protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
