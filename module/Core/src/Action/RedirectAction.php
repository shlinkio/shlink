<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\Action\Util\ErrorResponseBuilderTrait;
use Shlinkio\Shlink\Core\Exception\EntityDoesNotExistException;
use Shlinkio\Shlink\Core\Exception\InvalidShortCodeException;
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

    public function __construct(UrlShortenerInterface $urlShortener, VisitsTrackerInterface $visitTracker)
    {
        $this->urlShortener = $urlShortener;
        $this->visitTracker = $visitTracker;
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
        } catch (InvalidShortCodeException $e) {
            return $this->buildErrorResponse($request, $delegate);
        } catch (EntityDoesNotExistException $e) {
            return $this->buildErrorResponse($request, $delegate);
        }
    }
}
