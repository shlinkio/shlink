<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ShortUrl;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Validation\ShortUrlInputFilter;

class DefaultShortCodesLengthMiddleware implements MiddlewareInterface
{
    public function __construct(private int $defaultShortCodesLength)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var array $body */
        $body = $request->getParsedBody();
        if (! isset($body[ShortUrlInputFilter::SHORT_CODE_LENGTH])) {
            $body[ShortUrlInputFilter::SHORT_CODE_LENGTH] = $this->defaultShortCodesLength;
        }

        return $handler->handle($request->withParsedBody($body));
    }
}
