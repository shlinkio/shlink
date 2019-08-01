<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Exception;

use RuntimeException;
use Throwable;

class GeolocationDbUpdateFailedException extends RuntimeException implements ExceptionInterface
{
    /** @var bool */
    private $olderDbExists;

    public function __construct(bool $olderDbExists, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        $this->olderDbExists = $olderDbExists;
        parent::__construct($message, $code, $previous);
    }

    public static function create(bool $olderDbExists, ?Throwable $prev = null): self
    {
        return new self(
            $olderDbExists,
            'An error occurred while updating geolocation database, and an older version could not be found',
            0,
            $prev
        );
    }

    public function olderDbExists(): bool
    {
        return $this->olderDbExists;
    }
}
