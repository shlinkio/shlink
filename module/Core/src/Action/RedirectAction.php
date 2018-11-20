<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Core\Action\Util\ErrorResponseBuilderTrait;
use Shlinkio\Shlink\Core\Options;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class RedirectAction extends AbstractTrackingAction
{
    use ErrorResponseBuilderTrait;

    /** @var Options\NotFoundShortUrlOptions */
    private $notFoundOptions;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        VisitsTrackerInterface $visitTracker,
        Options\AppOptions $appOptions,
        Options\NotFoundShortUrlOptions $notFoundOptions,
        LoggerInterface $logger = null
    ) {
        parent::__construct($urlShortener, $visitTracker, $appOptions, $logger);
        $this->notFoundOptions = $notFoundOptions;
    }

    protected function createSuccessResp(string $longUrl): Response
    {
        // Return a redirect response to the long URL.
        // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
        return new RedirectResponse($longUrl);
    }

    protected function createErrorResp(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): Response {
        if ($this->notFoundOptions->isRedirectionEnabled()) {
            return new RedirectResponse($this->notFoundOptions->getRedirectTo());
        }

        return $this->buildErrorResponse($request, $handler);
    }
}
