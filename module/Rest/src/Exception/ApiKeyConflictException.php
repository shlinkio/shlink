<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use function sprintf;

class ApiKeyConflictException extends RuntimeException implements ExceptionInterface
{
    public static function forName(string $name): self
    {
        return new self(sprintf('An API key with name "%s" already exists', $name));
    }
}
