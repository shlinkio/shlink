<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function Functional\contains;
use function Shlinkio\Shlink\Common\json_decode;

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

        return $handler->handle($this->parseFromJson($request));
    }

    private function parseFromJson(Request $request): Request
    {
        $rawBody = $request->getBody()->__toString();
        if (empty($rawBody)) {
            return $request;
        }

        $parsedJson = json_decode($rawBody);
        return $request->withParsedBody($parsedJson);
    }
}
