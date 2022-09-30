<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Options\UrlShortenerOptions;

use function rtrim;

class TrimTrailingSlashMiddleware implements MiddlewareInterface
{
    private const SHORT_CODE_ATTR = 'shortCode';

    public function __construct(private readonly UrlShortenerOptions $options)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($this->resolveRequest($request));
    }

    private function resolveRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        // If multi-segment slugs are enabled together with trailing slashes, the "shortCode" attribute will include
        // ending slashes that we need to trim for a proper short code matching

        /** @var string|null $shortCode */
        $shortCode = $request->getAttribute(self::SHORT_CODE_ATTR);
        $shouldTrimSlash = $shortCode !== null && $this->options->trailingSlashEnabled;

        return  $shouldTrimSlash ? $request->withAttribute(self::SHORT_CODE_ATTR, rtrim($shortCode, '/')) : $request;
    }
}
