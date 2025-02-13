<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractRestAction implements RequestHandlerInterface, RequestMethodInterface, StatusCodeInterface
{
    protected const string ROUTE_PATH = '';
    protected const array ROUTE_ALLOWED_METHODS = [];

    public static function getRouteDef(array $prevMiddleware = [], array $postMiddleware = []): array
    {
        return [
            'name' => static::class,
            'middleware' => [...$prevMiddleware, static::class, ...$postMiddleware],
            'path' => static::ROUTE_PATH,
            'allowed_methods' => static::ROUTE_ALLOWED_METHODS,
        ];
    }
}
