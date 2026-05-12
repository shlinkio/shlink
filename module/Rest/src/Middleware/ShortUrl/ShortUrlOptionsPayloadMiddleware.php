<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Config\Options\UrlShortenerOptions;

/**
 * Sets some values and defaults in the request payload based on the short URL options, so that they can be later used
 * for model hydration.
 */
readonly class ShortUrlOptionsPayloadMiddleware implements MiddlewareInterface
{
    public function __construct(private UrlShortenerOptions $urlShortenerOptions)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        if (! isset($body['shortCodeLength'])) {
            $body['shortCodeLength'] = $this->urlShortenerOptions->defaultShortCodesLength;
        }

        $body['shortUrlMode'] = $this->urlShortenerOptions->mode;
        $body['multiSegmentSlugsEnabled'] = $this->urlShortenerOptions->multiSegmentSlugsEnabled;

        return $handler->handle($request->withParsedBody($body));
    }
}
