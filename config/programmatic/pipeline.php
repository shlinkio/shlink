<?php

declare(strict_types=1);

namespace Shlinkio\Shlink;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\ProblemDetails;
use Mezzio\Router;
use PhpMiddleware\RequestId\RequestIdMiddleware;
use RKA\Middleware\IpAddress;
use Shlinkio\Shlink\Common\Middleware\ContentLengthMiddleware;

return static function (Application $app): void {
    $app->pipe([
        ContentLengthMiddleware::class,
        RequestIdMiddleware::class,
        ErrorHandler::class,
        Rest\Middleware\CrossDomainMiddleware::class,
    ]);
    $app->pipe('/rest', [
        ProblemDetails\ProblemDetailsMiddleware::class,
    ]);
    $app->pipe([
        Common\Middleware\CloseDbConnectionMiddleware::class,
        Router\Middleware\RouteMiddleware::class,
        Router\Middleware\ImplicitHeadMiddleware::class,
    ]);
    $app->pipe('/rest', [
        Rest\Middleware\ErrorHandler\BackwardsCompatibleProblemDetailsHandler::class,
        Router\Middleware\ImplicitOptionsMiddleware::class,
        Rest\Middleware\BodyParserMiddleware::class,
        Rest\Middleware\AuthenticationMiddleware::class,
    ]);
    $app->pipe([
        Router\Middleware\DispatchMiddleware::class,
    ]);
    $app->pipe('/rest', [
        ProblemDetails\ProblemDetailsNotFoundHandler::class,
    ]);
    $app->pipe([
        // This middleware is in front of tracking actions explicitly. Putting here for orphan visits tracking
        IpAddress::class,
        Core\ErrorHandler\NotFoundTypeResolverMiddleware::class,
        Core\ShortUrl\Middleware\ExtraPathRedirectMiddleware::class,
        Core\ErrorHandler\NotFoundTrackerMiddleware::class,
        Core\ErrorHandler\NotFoundRedirectHandler::class,
        Core\ErrorHandler\NotFoundTemplateHandler::class,
    ]);
};
