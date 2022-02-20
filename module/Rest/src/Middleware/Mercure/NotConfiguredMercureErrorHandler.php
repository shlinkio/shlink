<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Middleware\Mercure;

use Mezzio\ProblemDetails\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Shlinkio\Shlink\Rest\Exception\MercureException;

class NotConfiguredMercureErrorHandler implements MiddlewareInterface
{
    public function __construct(private ProblemDetailsResponseFactory $respFactory, private LoggerInterface $logger)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MercureException $e) {
            // Throwing this kind of exception makes a big error trace to be logged, for anyone who has decided to not
            // use mercure.
            // It happens every time the shlink-web-client is opened, so this mitigates the problem by just logging a
            // simple warning, and casting the exception to a response on the fly.
            $this->logger->warning($e->getMessage());
            return $this->respFactory->createResponseFromThrowable($request, $e);
        }
    }
}
