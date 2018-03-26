<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Action\Util\ErrorResponseBuilderTrait;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
use Shlinkio\Shlink\Core\Options\AppOptions;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class RedirectAction implements MiddlewareInterface
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
     * @param Request $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
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

            // Return a redirect response to the long URL.
            // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
            return new RedirectResponse($longUrl);
        } catch (InvalidShortCodeException $e) {
            return $this->buildErrorResponse($request, $handler);
        } catch (EntityDoesNotExistException $e) {
            return $this->buildErrorResponse($request, $handler);
        }
    }
}
