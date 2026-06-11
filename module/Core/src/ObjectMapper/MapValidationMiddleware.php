<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ObjectMapper;

use CuyZ\Valinor\Mapper\MappingError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shlinkio\Shlink\Core\Exception\ValidationException;

/**
 * Captures valinor library errors and converts them to ValidationExceptions
 */
class MapValidationMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (MappingError $e) {
            $errors = $e->messages()->errors();
            $invalidData = [];

            foreach ($errors as $error) {
                $invalidData[$error->name()] = $error->toString();
            }

            throw ValidationException::fromArray($invalidData, $e);
        }
    }
}
