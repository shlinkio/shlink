<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Service\ShortUrlService;
use Acelaya\UrlShortener\Service\ShortUrlServiceInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Stratigility\MiddlewareInterface;

class ListShortcodesMiddleware implements MiddlewareInterface
{
    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;

    /**
     * ListShortcodesMiddleware constructor.
     * @param ShortUrlServiceInterface|ShortUrlService $shortUrlService
     *
     * @Inject({ShortUrlService::class})
     */
    public function __construct(ShortUrlServiceInterface $shortUrlService)
    {
        $this->shortUrlService = $shortUrlService;
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
        try {
            $shortUrls = $this->shortUrlService->listShortUrls();

            return new JsonResponse([
                'shortUrls' => [
                    'data' => $shortUrls,
//                    'pagination' => [],
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => 'Unexpected error occured',
            ], 500);
        }
    }
}
