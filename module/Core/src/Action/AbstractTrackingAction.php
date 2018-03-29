<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\Util\ErrorResponseBuilderTrait;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;

abstract class AbstractTrackingAction implements MiddlewareInterface
{
    use ErrorResponseBuilderTrait;

    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var VisitsTrackerInterface
     */
    private $visitTracker;
    /**
     * @var AppOptions
     */
    private $appOptions;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        VisitsTrackerInterface $visitTracker,
        AppOptions $appOptions
    ) {
        $this->urlShortener = $urlShortener;
        $this->visitTracker = $visitTracker;
        $this->appOptions = $appOptions;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $shortCode = $request->getAttribute('shortCode', '');
        $query = $request->getQueryParams();
        $disableTrackParam = $this->appOptions->getDisableTrackParam();

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);

            // Track visit to this short code
            if ($disableTrackParam === null || ! \array_key_exists($disableTrackParam, $query)) {
                $this->visitTracker->track($shortCode, $request);
            }

            return $this->createResp($longUrl);
        } catch (InvalidShortCodeException | EntityDoesNotExistException $e) {
            return $this->buildErrorResponse($request, $handler);
        }
    }

    abstract protected function createResp(string $longUrl): ResponseInterface;
}
