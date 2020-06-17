<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use function sprintf;

class RedirectAction extends AbstractTrackingAction implements StatusCodeInterface
{
    private Options\UrlShortenerOptions $urlShortenerOptions;

    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        VisitsTrackerInterface $visitTracker,
        Options\AppOptions $appOptions,
        Options\UrlShortenerOptions $urlShortenerOptions,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($urlResolver, $visitTracker, $appOptions, $logger);
        $this->urlShortenerOptions = $urlShortenerOptions;
    }

    protected function createSuccessResp(string $longUrl): Response
    {
        $statusCode = $this->urlShortenerOptions->redirectStatusCode();
        $headers = $statusCode === self::STATUS_FOUND ? [] : [
            'Cache-Control' => sprintf('private,max-age=%s', $this->urlShortenerOptions->redirectCacheLifetime()),
        ];

        return new RedirectResponse($longUrl, $statusCode, $headers);
    }

    protected function createErrorResp(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
