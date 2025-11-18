<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use function sprintf;

class ApiKeyNotFoundException extends RuntimeException implements ExceptionInterface
{
    public static function forName(string $name): self
    {
        return new self(sprintf('API key with name "%s" not found', $name));
    }
}
