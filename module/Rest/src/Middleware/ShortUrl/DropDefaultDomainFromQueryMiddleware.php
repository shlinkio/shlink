<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DropDefaultDomainFromQueryMiddleware implements MiddlewareInterface
{
    private string $defaultDomain;

    public function __construct(string $defaultDomain)
    {
        $this->defaultDomain = $defaultDomain;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        if (isset($query['domain']) && $query['domain'] === $this->defaultDomain) {
            unset($query['domain']);
            $request = $request->withQueryParams($query);
        }

        return $handler->handle($request);
    }
}
