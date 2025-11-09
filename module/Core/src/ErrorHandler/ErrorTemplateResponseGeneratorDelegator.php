<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ErrorHandler;

use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\ProblemDetails\Exception\ProblemDetailsExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function method_exists;
use function property_exists;
use function str_contains;
use function str_starts_with;
use function strtolower;

class ErrorTemplateResponseGeneratorDelegator implements DelegatorFactoryInterface
{
    /**
     * @param mixed $name
     */
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        array|null $options = null,
    ): ErrorHandler {
        $errorTemplateHandler = new ErrorTemplateHandler();

        // Create a custom response generator that uses HTML templates for non-REST requests
        $responseGenerator = function (
            Throwable $error,
            ServerRequestInterface $request,
            ResponseInterface $response,
        ) use ($errorTemplateHandler): ResponseInterface {
            // Only use HTML templates for non-REST requests
            $path = $request->getUri()->getPath();
            if (str_starts_with($path, '/rest')) {
                return $response;
            }

            // Only use HTML templates for HTML requests (not API calls expecting JSON)
            $acceptHeader = $request->getHeaderLine('Accept');
            if (
                $acceptHeader !== ''
                && str_contains($acceptHeader, 'application/json')
                && !str_contains($acceptHeader, 'text/html')
            ) {
                return $response;
            }

            // Get status code from response
            // If response doesn't have a valid error code, try to get it from the exception
            $statusCode = $response->getStatusCode();
            if ($statusCode < 400) {
                // For ProblemDetailsExceptionInterface, use the status property (preferred and safer)
                // All Shlink exceptions implement this interface, so this covers all cases
                if ($error instanceof ProblemDetailsExceptionInterface && property_exists($error, 'status')) {
                    $statusCode = $error->status;
                }
                // Note: We don't use getCode() as fallback because:
                // 1. All Shlink exceptions implement ProblemDetailsExceptionInterface
                // 2. getCode() might not be an HTTP status code (could be any integer)
                // 3. If response has no error code and exception has no status, default to 500
            }

            // Only handle error status codes (4xx and 5xx)
            if ($statusCode < 400) {
                return $response;
            }

            // Create HTML response from template
            $htmlResponse = $errorTemplateHandler->createErrorResponse($error, $statusCode);

            // Copy important headers from original response
            foreach ($response->getHeaders() as $name => $values) {
                if (strtolower($name) !== 'content-type') {
                    $htmlResponse = $htmlResponse->withHeader($name, $values);
                }
            }

            return $htmlResponse;
        };

        /** @var ErrorHandler $errorHandler */
        $errorHandler = $callback();

        // Try to set the response generator using reflection if the method exists
        // @phpstan-ignore-next-line - method_exists check is needed at runtime, even if PHPStan knows the type
        if (method_exists($errorHandler, 'setResponseGenerator')) {
            $errorHandler->setResponseGenerator($responseGenerator);
        } else {
            // If setResponseGenerator doesn't exist, create a new ErrorHandler with the response generator
            $responseFactory = $container->get('Psr\Http\Message\ResponseFactoryInterface');
            $errorHandler = new ErrorHandler($responseFactory, $responseGenerator);
        }

        return $errorHandler;
    }
}
