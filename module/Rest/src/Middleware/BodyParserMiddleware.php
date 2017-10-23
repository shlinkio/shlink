<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Common\Exception\RuntimeException;

class BodyParserMiddleware implements MiddlewareInterface, RequestMethodInterface
{
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
        $method = $request->getMethod();
        $currentParams = $request->getParsedBody();

        // In requests that do not allow body or if the body has already been parsed, continue to next middleware
        if (! empty($currentParams) || in_array($method, [
            self::METHOD_GET,
            self::METHOD_HEAD,
            self::METHOD_OPTIONS,
        ], true)) {
            return $delegate->process($request);
        }

        // If the accepted content is JSON, try to parse the body from JSON
        $contentType = $this->getRequestContentType($request);
        if (in_array($contentType, ['application/json', 'text/json', 'application/x-json'], true)) {
            return $delegate->process($this->parseFromJson($request));
        }

        return $delegate->process($this->parseFromUrlEncoded($request));
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
