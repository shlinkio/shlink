<?php
namespace Shlinkio\Shlink\Core\Action;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Service\UrlShortener;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTracker;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewareInterface;

class RedirectAction implements MiddlewareInterface
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var VisitsTrackerInterface
     */
    private $visitTracker;

    /**
     * RedirectMiddleware constructor.
     * @param UrlShortenerInterface $urlShortener
     * @param VisitsTrackerInterface $visitTracker
     *
     * @Inject({UrlShortener::class, VisitsTracker::class})
     */
    public function __construct(UrlShortenerInterface $urlShortener, VisitsTrackerInterface $visitTracker)
    {
        $this->urlShortener = $urlShortener;
        $this->visitTracker = $visitTracker;
    }

    /**
     * Process an incoming request and/or response.
     *
     * Accepts a server-side request and a response instance, and does
     * something with them.
     *
     * If the response is not complete and/or further processing would not
     * interfere with the work done in the middleware, or if the middleware
     * wants to delegate to another process, it can use the `$out` callable
     * if present.
     *
     * If the middleware does not return a value, execution of the current
     * request is considered complete, and the response instance provided will
     * be considered the response to return.
     *
     * Alternately, the middleware may return a response instance.
     *
     * Often, middleware will `return $out();`, with the assumption that a
     * later middleware will return a response.
     *
     * @param Request $request
     * @param Response $response
     * @param null|callable $out
     * @return null|Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $shortCode = $request->getAttribute('shortCode', '');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);

            // If provided shortCode does not belong to a valid long URL, dispatch next middleware, which will trigger
            // a not-found error
            if (! isset($longUrl)) {
                return $this->notFoundResponse($request, $response, $out);
            }

            // Track visit to this short code
            $this->visitTracker->track($shortCode);

            // Return a redirect response to the long URL.
            // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
            return new RedirectResponse($longUrl);
        } catch (\Exception $e) {
            // In case of error, dispatch 404 error
            return $this->notFoundResponse($request, $response, $out);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    protected function notFoundResponse(Request $request, Response $response, callable $out)
    {
        return $out($request, $response->withStatus(404), 'Not Found');
    }
}
