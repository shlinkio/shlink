<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\ErrorHandler\Model\NotFoundType;

class NotFoundTypeResolverMiddleware implements MiddlewareInterface
{
    public function __construct(private string $shlinkBasePath)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $notFoundType = NotFoundType::fromRequest($request, $this->shlinkBasePath);
        return $handler->handle($request->withAttribute(NotFoundType::class, $notFoundType));
    }
}
