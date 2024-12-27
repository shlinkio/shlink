<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use RuntimeException;
use Throwable;

class GeolocationDbUpdateFailedException extends RuntimeException implements ExceptionInterface
{
    private function __construct(string $message, public readonly bool $olderDbExists, Throwable|null $prev = null)
    {
        parent::__construct($message, previous: $prev);
    }

    public static function withOlderDb(Throwable|null $prev = null): self
    {
        return new self(
            'An error occurred while updating geolocation database, but an older DB is already present.',
            olderDbExists: true,
            prev: $prev,
        );
    }

    public static function withoutOlderDb(Throwable|null $prev = null): self
    {
        return new self(
            'An error occurred while updating geolocation database, and an older version could not be found.',
            olderDbExists: false,
            prev: $prev,
        );
    }
}
