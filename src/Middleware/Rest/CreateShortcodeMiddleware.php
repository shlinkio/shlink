<?php
namespace Acelaya\UrlShortener\Middleware\Rest;

use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\UrlShortener\Util\RestUtils;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Uri;
use Zend\Stratigility\MiddlewareInterface;

class CreateShortcodeMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlShortener|UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $domainConfig;

    /**
     * GenerateShortcodeMiddleware constructor.
     *
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param array $domainConfig
     *
     * @Inject({UrlShortener::class, "config.url_shortener.domain"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, array $domainConfig)
    {
        $this->urlShortener = $urlShortener;
        $this->domainConfig = $domainConfig;
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
        $postData = $request->getParsedBody();
        if (! isset($postData['longUrl'])) {
            return new JsonResponse([
                'error' => RestUtils::INVALID_ARGUMENT_ERROR,
                'message' => 'A URL was not provided',
            ], 400);
        }
        $longUrl = $postData['longUrl'];

        try {
            $shortcode = $this->urlShortener->urlToShortCode(new Uri($longUrl));
            $shortUrl = (new Uri())->withPath($shortcode)
                                   ->withScheme($this->domainConfig['schema'])
                                   ->withHost($this->domainConfig['hostname']);

            return new JsonResponse([
                'longUrl' => $longUrl,
                'shortUrl' => $shortUrl->__toString(),
            ]);
        } catch (InvalidUrlException $e) {
            return new JsonResponse([
                'error' => RestUtils::getRestErrorCodeFromException($e),
                'message' => sprintf('Provided URL "%s" is invalid. Try with a different one.', $longUrl),
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => RestUtils::UNKNOWN_ERROR,
                'message' => sprintf('Provided URL "%s" is invalid. Try with a different one.', $longUrl),
            ], 500);
        }
    }
}
