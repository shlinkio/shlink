<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Exception;

use RuntimeException;
use Throwable;

class GeolocationDbUpdateFailedException extends RuntimeException implements ExceptionInterface
{
    private bool $olderDbExists;

    public static function create(bool $olderDbExists, ?Throwable $prev = null): self
    {
        $e = new self(
            'An error occurred while updating geolocation database, and an older version could not be found',
            0,
            $prev,
        );
        $e->olderDbExists = $olderDbExists;

        return $e;
    }

    public function olderDbExists(): bool
    {
        return $this->olderDbExists;
    }
}
