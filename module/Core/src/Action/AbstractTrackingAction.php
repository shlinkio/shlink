<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Visit\RequestTrackerInterface;

use const Shlinkio\Shlink\REDIRECT_URL_REQUEST_ATTRIBUTE;

abstract class AbstractTrackingAction implements MiddlewareInterface, RequestMethodInterface
{
    public function __construct(
        private readonly ShortUrlResolverInterface $urlResolver,
        private readonly RequestTrackerInterface $requestTracker,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identifier = ShortUrlIdentifier::fromRedirectRequest($request);

        try {
            $shortUrl = $this->urlResolver->resolveEnabledShortUrl($identifier);
            $response = $this->createSuccessResp($shortUrl, $request);
            $this->requestTracker->trackIfApplicable($shortUrl, $request->withAttribute(
                REDIRECT_URL_REQUEST_ATTRIBUTE,
                $response->hasHeader('Location') ? $response->getHeaderLine('Location') : null,
            ));

            return $response;
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
