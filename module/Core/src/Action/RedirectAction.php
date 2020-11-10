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
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Shlinkio\Shlink\Core\Util\RedirectResponseHelperInterface;

class RedirectAction extends AbstractTrackingAction implements StatusCodeInterface
{
    private RedirectResponseHelperInterface $redirectResponseHelper;

    public function __construct(
        ShortUrlResolverInterface $urlResolver,
        VisitsTrackerInterface $visitTracker,
        Options\AppOptions $appOptions,
        RedirectResponseHelperInterface $redirectResponseHelper,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($urlResolver, $visitTracker, $appOptions, $logger);
        $this->redirectResponseHelper = $redirectResponseHelper;
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
