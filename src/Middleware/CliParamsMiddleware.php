<?php
namespace Acelaya\UrlShortener\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Expressive\Router\RouteResult;
use Zend\Stratigility\MiddlewareInterface;

class CliParamsMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    private $argv;

    public function __construct(array $argv)
    {
        $this->argv = $argv;
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
        // When not in CLI, just call next middleware
        if (php_sapi_name() !== 'cli') {
            return $out($request, $response);
        }

        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult->isSuccess()) {
            return $out($request, $response);
        }

        // Inject ARGV params as request attributes
        if ($routeResult->getMatchedRouteName() === 'cli-generate-shortcode') {
            $request = $request->withAttribute('longUrl', isset($this->argv[2]) ? $this->argv[2] : null);
        }

        return $out($request, $response);
    }
}
