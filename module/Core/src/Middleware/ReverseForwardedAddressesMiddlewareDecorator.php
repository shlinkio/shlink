<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_reverse;
use function explode;
use function implode;

/**
 * Decorates a middleware to make sure it gets called with a list of reversed addresses in `X-Forwarded-For`.
 *
 * This is a workaround for a change in behavior introduced in akrabat/ip-address-middleware 2.5, which now
 * takes the first non-trusted-proxy address in that header, starting from the right, instead of the first
 * address starting from the left.
 * That change breaks Shlink's visitor IP resolution when more than one proxy is used, and trusted proxies
 * are not explicitly set for akrabat/ip-address-middleware (which Shlink does not do).
 *
 * A proper solution would require allowing trusted proxies to be configurable, and apply this logic conditionally, only
 * if trusted proxies are not set.
 *
 * @see https://github.com/akrabat/ip-address-middleware/pull/51
 */
readonly class ReverseForwardedAddressesMiddlewareDecorator implements MiddlewareInterface
{
    public const string FORWARDED_FOR_HEADER = 'X-Forwarded-For';

    public function __construct(private MiddlewareInterface $wrappedMiddleware)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->hasHeader(self::FORWARDED_FOR_HEADER)) {
            $request = $request->withHeader(
                self::FORWARDED_FOR_HEADER,
                implode(',', array_reverse(explode(',', $request->getHeaderLine(self::FORWARDED_FOR_HEADER)))),
            );
        }

        return $this->wrappedMiddleware->process($request, $handler);
    }
}
