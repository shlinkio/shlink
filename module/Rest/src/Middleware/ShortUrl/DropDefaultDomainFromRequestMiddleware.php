<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DropDefaultDomainFromRequestMiddleware implements MiddlewareInterface
{
    public function __construct(private string $defaultDomain)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        $request = $request->withQueryParams($this->sanitizeDomainFromPayload($request->getQueryParams()))
                           ->withParsedBody($this->sanitizeDomainFromPayload($body));

        return $handler->handle($request);
    }

    private function sanitizeDomainFromPayload(array $payload): array
    {
        if (isset($payload['domain']) && $payload['domain'] === $this->defaultDomain) {
            unset($payload['domain']);
        }

        return $payload;
    }
}
