<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\ErrorHandler;

use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Rest\Exception\BackwardsCompatibleProblemDetailsException;

use function version_compare;

/** @deprecated */
class BackwardsCompatibleProblemDetailsHandler implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ProblemDetailsExceptionInterface $e) {
            $version = $request->getAttribute('version') ?? '2';
            throw version_compare($version, '3', '>=')
                ? $e
                : BackwardsCompatibleProblemDetailsException::fromProblemDetails($e);
        }
    }
}
