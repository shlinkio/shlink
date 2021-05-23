<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;
use function explode;
use function str_contains;
use function strtolower;

class CreateShortUrlContentNegotiationMiddleware implements MiddlewareInterface
{
    private const PLAIN_TEXT = 'text';
    private const JSON = 'json';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // If the response is not JSON, return it as is
        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $query = $request->getQueryParams();
        $acceptedType = isset($query['format'])
            ? $this->determineAcceptTypeFromQuery($query)
            : $this->determineAcceptTypeFromHeader($request->getHeaderLine('Accept'));

        // If JSON was requested, return the response from next handler as is
        if ($acceptedType === self::JSON) {
            return $response;
        }

        // If requested, return a plain text response containing the short URL only
        $resp = (new Response())->withHeader('Content-Type', 'text/plain');
        $body = $resp->getBody();
        $body->write($this->determineBody($response));
        $body->rewind();
        return $resp;
    }

    private function determineAcceptTypeFromQuery(array $query): string
    {
        if (! isset($query['format'])) {
            return self::JSON;
        }

        $format = strtolower($query['format']);
        return $format === 'txt' ? self::PLAIN_TEXT : self::JSON;
    }

    private function determineAcceptTypeFromHeader(string $acceptValue): string
    {
        $accepts = explode(',', $acceptValue);
        $accept = strtolower(array_shift($accepts));
        return str_contains($accept, 'text/plain') ? self::PLAIN_TEXT : self::JSON;
    }

    private function determineBody(JsonResponse $resp): string
    {
        $payload = $resp->getPayload();
        return $payload['shortUrl'] ?? $payload['error'] ?? '';
    }
}
