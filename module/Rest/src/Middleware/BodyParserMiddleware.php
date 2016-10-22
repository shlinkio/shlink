<?php
namespace Shlinkio\Shlink\Rest\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Zend\Stratigility\MiddlewareInterface;

class BodyParserMiddleware implements MiddlewareInterface
{
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
        $method = $request->getMethod();
        $currentParams = $request->getParsedBody();

        // In requests that do not allow body or if the body has already been parsed, continue to next middleware
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS']) || ! empty($currentParams)) {
            return $out($request, $response);
        }

        // If the accepted content is JSON, try to parse the body from JSON
        $contentType = $this->getRequestContentType($request);
        if (in_array($contentType, ['application/json', 'text/json', 'application/x-json'])) {
            return $out($this->parseFromJson($request), $response);
        }

        return $out($this->parseFromUrlEncoded($request), $response);
    }

    /**
     * @param Request $request
     * @return string
     */
    protected function getRequestContentType(Request $request)
    {
        $contentType = $request->getHeaderLine('Content-type');
        $contentTypes = explode(';', $contentType);
        return trim(array_shift($contentTypes));
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function parseFromJson(Request $request)
    {
        $rawBody = (string) $request->getBody();
        if (empty($rawBody)) {
            return $request;
        }

        $parsedJson = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Error when parsing JSON request body: %s', json_last_error_msg()));
        }

        return $request->withParsedBody($parsedJson);
    }

    /**
     * @param Request $request
     * @return Request
     */
    protected function parseFromUrlEncoded(Request $request)
    {
        $rawBody = (string) $request->getBody();
        if (empty($rawBody)) {
            return $request;
        }

        $parsedBody = [];
        parse_str($rawBody, $parsedBody);

        return $request->withParsedBody($parsedBody);
    }
}
