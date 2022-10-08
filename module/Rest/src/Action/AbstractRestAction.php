<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Mezzio\Application;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractRestAction implements RequestHandlerInterface, RequestMethodInterface, StatusCodeInterface
{
    private const ROUTES_PREFIX = '/rest/v{version:1|2|3}';
    private const UNVERSIONED_ROUTES_PREFIX = '/rest';
    public const UNVERSIONED_NAME_PREFIX = 'unversioned_health';
    protected const ROUTE_PATH = '';
    protected const ROUTE_ALLOWED_METHODS = [];

    public static function register(Application $app, array $prevMiddleware = [], array $postMiddleware = []): void
    {
        self::doRegister($app, self::ROUTES_PREFIX, '', $prevMiddleware, $postMiddleware);
    }

    public static function registerUnversioned(
        Application $app,
        array $prevMiddleware = [],
        array $postMiddleware = [],
    ): void {
        self::doRegister(
            $app,
            self::UNVERSIONED_ROUTES_PREFIX,
            self::UNVERSIONED_NAME_PREFIX,
            $prevMiddleware,
            $postMiddleware,
        );
    }

    private static function doRegister(
        Application $app,
        string $routePrefix,
        string $namePrefix,
        array $prevMiddleware = [],
        array $postMiddleware = [],
    ): void {
        $app->route(
            $routePrefix . static::ROUTE_PATH,
            [...$prevMiddleware, static::class, ...$postMiddleware],
            static::ROUTE_ALLOWED_METHODS,
            $namePrefix . static::class,
        );
    }
}
