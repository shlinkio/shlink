<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortCode;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\JsonResponse;

class CreateShortCodeContentNegotiationMiddleware implements MiddlewareInterface
{
    private const PLAIN_TEXT = 'text';
    private const JSON = 'json';

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var JsonResponse $response */
        $response = $handler->handle($request);
        $acceptedType = $this->determineAcceptedType($request);
        if ($acceptedType === self::JSON) {
            return $response;
        }

        // If requested, return a plain text response containing the short URL only
        $resp = (new Response())->withHeader('Content-Type', 'text/plain');
        $body = $resp->getBody();
        $body->write($response->getPayload()['shortUrl'] ?? '');
        $body->rewind();
        return $resp;
    }

    private function determineAcceptedType(ServerRequestInterface $request): string
    {
        $accepts = \explode(',', $request->getHeaderLine('Accept'));
        $accept = \strtolower(\array_shift($accepts));
        return \strpos($accept, 'text/plain') !== false ? self::PLAIN_TEXT : self::JSON;
    }
}
