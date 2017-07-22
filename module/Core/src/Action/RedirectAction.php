<?php
namespace Shlinkio\Shlink\Core\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shlinkio\Shlink\Core\Service\UrlShortenerInterface;
use Shlinkio\Shlink\Core\Service\VisitsTrackerInterface;
use Zend\Diactoros\Response\RedirectResponse;

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
     * @var null|LoggerInterface
     */
    private $logger;

    public function __construct(
        UrlShortenerInterface $urlShortener,
        VisitsTrackerInterface $visitTracker,
        LoggerInterface $logger = null
    ) {
        $this->urlShortener = $urlShortener;
        $this->visitTracker = $visitTracker;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param Request $request
     * @param DelegateInterface $delegate
     *
     * @return Response
     */
    public function process(Request $request, DelegateInterface $delegate)
    {
        $shortCode = $request->getAttribute('shortCode', '');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);

            // If provided shortCode does not belong to a valid long URL, dispatch next middleware, which will trigger
            // a not-found error
            if ($longUrl === null) {
                return $delegate->process($request);
            }

            // Track visit to this short code
            $this->visitTracker->track($shortCode, $request);

            // Return a redirect response to the long URL.
            // Use a temporary redirect to make sure browsers always hit the server for analytics purposes
            return new RedirectResponse($longUrl);
        } catch (\Exception $e) {
            // In case of error, dispatch 404 error
            $this->logger->error('Error redirecting to long URL.' . PHP_EOL . $e);
            return $delegate->process($request);
        }
    }
}
