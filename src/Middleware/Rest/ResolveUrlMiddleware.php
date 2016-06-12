<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class ResolveUrlMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;

    /**
     * ResolveUrlMiddleware constructor.
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
        $shortCode = $request->getAttribute('shortCode');

        try {
            $longUrl = $this->urlShortener->shortCodeToUrl($shortCode);
            if (! isset($longUrl)) {
                return new JsonResponse([
                    'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                    'message' => sprintf('No URL found for shortcode "%s"', $shortCode),
                ], 400);
            }

            return new JsonResponse([
                'longUrl' => $longUrl,
            ]);
        } catch (InvalidShortCodeException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided short code "%s" has an invalid format', $shortCode),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
