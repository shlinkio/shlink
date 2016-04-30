<?php
namespace Acelaya\UrlShortener\Middleware\CliRoutable;

use Acelaya\UrlShortener\Exception\InvalidUrlException;
use Acelaya\UrlShortener\Service\UrlShortener;
use Acelaya\UrlShortener\Service\UrlShortenerInterface;
use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Uri;
use Zend\Stratigility\MiddlewareInterface;

class GenerateShortcodeMiddleware implements MiddlewareInterface
{
    /**
     * @var UrlShortenerInterface
     */
    private $urlShortener;
    /**
     * @var array
     */
    private $config;

    /**
     * GenerateShortcodeMiddleware constructor.
     *
     * @param UrlShortenerInterface|UrlShortener $urlShortener
     * @param array $config
     *
     * @Inject({UrlShortener::class, "config.url-shortener"})
     */
    public function __construct(UrlShortenerInterface $urlShortener, array $config)
    {
        $this->urlShortener = $urlShortener;
        $this->config = $config;
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
        $longUrl = $request->getAttribute('longUrl');

        try {
            if (! isset($longUrl)) {
                $response->getBody()->write('A URL was not provided!' . PHP_EOL);
                return;
            }

            $shortcode = $this->urlShortener->urlToShortCode(new Uri($longUrl));
            $shortUrl = (new Uri())->withPath($shortcode)
                                   ->withScheme($this->config['schema'])
                                   ->withHost($this->config['hostname']);

            $response->getBody()->write(
                sprintf('Processed URL "%s".%sGenerated short URL "%s"', $longUrl, PHP_EOL, $shortUrl) . PHP_EOL
            );
        } catch (InvalidUrlException $e) {
            $response->getBody()->write(
                sprintf('Provided URL "%s" is invalid. Try with a different one.', $longUrl) . PHP_EOL
            );
        } catch (\Exception $e) {
            $response->getBody()->write($e);
        } finally {
            return $response;
        }
    }
}
