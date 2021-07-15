<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

abstract class AbstractTrackingAction implements MiddlewareInterface, RequestMethodInterface
{
    public function __construct(
        private ShortUrlResolverInterface $urlResolver,
        private RequestTrackerInterface $requestTracker,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);
            $this->requestTracker->trackIfApplicable($shortUrl, $request);

            return $this->createSuccessResp($shortUrl, $request);
        } catch (ShortUrlNotFoundException) {
            return $this->createErrorResp($request, $handler);
        }
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
