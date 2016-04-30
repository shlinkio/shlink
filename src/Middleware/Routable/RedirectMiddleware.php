<?php
namespace Acelaya\UrlShortener\Middleware\Routable;

use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Stratigility\MiddlewareInterface;

class RedirectMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;

    /**
     * RedirectMiddleware constructor.
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     *
     * @Inject({UrlShortener::class})
     */
    public function __construct(UrlShortenerInterface $urlShortener)
    {
        $this->urlShortener = $urlShortener;
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

            // If provided shortCode does not belong to a valid long URL, dispatch next middleware, which is 404
            // middleware
            if (! isset($longUrl)) {
                return $out($request, $response);
            }

            // Return a redirect response to the long URL
            return new RedirectResponse($longUrl, 301);
        } catch (\Exception $e) {
            // In case of error, dispatch 404 error
            return $out($request, $response);
        }
    }
}
