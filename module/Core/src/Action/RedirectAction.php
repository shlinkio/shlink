<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Helper\ShortUrlRedirectionBuilderInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;
use Shlinkio\Shlink\Core\Visit\VisitsTrackerInterface;

class RedirectAction extends AbstractTrackingAction implements StatusCodeInterface
{
    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        VisitsTrackerInterface $visitTracker,
        ShortUrlRedirectionBuilderInterface $redirectionBuilder,
        Options\TrackingOptions $trackingOptions,
        private RedirectResponseHelperInterface $redirectResponseHelper,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($urlResolver, $visitTracker, $redirectionBuilder, $trackingOptions, $logger);
    }

    protected function createSuccessResp(string $longUrl): Response
    {
        return $this->redirectResponseHelper->buildRedirectResponse($longUrl);
    }

    protected function createErrorResp(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        return $handler->handle($request);
    }
}
