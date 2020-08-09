<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;
use function explode;
use function Functional\contains;
use function parse_str;
use function Shlinkio\Shlink\Common\json_decode;
use function trim;

class BodyParserMiddleware implements MiddlewareInterface, RequestMethodInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $method = $request->getMethod();
        $currentParams = $request->getParsedBody();

        // In requests that do not allow body or if the body has already been parsed, continue to next middleware
        if (
            ! empty($currentParams)
            || contains([
                self::METHOD_GET,
                self::METHOD_HEAD,
                self::METHOD_OPTIONS,
            ], $method)
        ) {
            return $handler->handle($request);
        }

        // If the accepted content is JSON, try to parse the body from JSON
        $contentType = $this->getRequestContentType($request);
        if (contains(['application/json', 'text/json', 'application/x-json'], $contentType)) {
            return $handler->handle($this->parseFromJson($request));
        }

        return $handler->handle($this->parseFromUrlEncoded($request));
    }

    private function getRequestContentType(Request $request): string
    {
        $contentType = $request->getHeaderLine('Content-type');
        $contentTypes = explode(';', $contentType);
        return trim(array_shift($contentTypes));
    }

    private function parseFromJson(Request $request): Request
    {
        $rawBody = (string) $request->getBody();
        if (empty($rawBody)) {
            return $request;
        }

        $parsedJson = json_decode($rawBody);
        return $request->withParsedBody($parsedJson);
    }

    /**
     * @deprecated To be removed on Shlink v3.0.0, supporting only JSON requests.
     */
    private function parseFromUrlEncoded(Request $request): Request
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
