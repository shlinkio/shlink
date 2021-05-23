<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

class GeolocationDbUpdateFailedException extends RuntimeException implements ExceptionInterface
{
    private bool $olderDbExists;

    private function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function withOlderDb(?Throwable $prev = null): self
    {
        $e = new self(
            'An error occurred while updating geolocation database, but an older DB is already present.',
            0,
            $prev,
        );
        $e->olderDbExists = true;

        return $e;
    }

    public static function withoutOlderDb(?Throwable $prev = null): self
    {
        $e = new self(
            'An error occurred while updating geolocation database, and an older version could not be found.',
            0,
            $prev,
        );
        $e->olderDbExists = false;

        return $e;
    }

    /**
     * @param mixed $buildEpoch
     */
    public static function withInvalidEpochInOldDb($buildEpoch): self
    {
        $e = new self(sprintf(
            'Build epoch with value "%s" from existing geolocation database, could not be parsed to integer.',
            $buildEpoch,
        ));
        $e->olderDbExists = true;

        return $e;
    }

    public function olderDbExists(): bool
    {
        return $this->olderDbExists;
    }
}
