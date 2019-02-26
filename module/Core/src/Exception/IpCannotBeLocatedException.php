<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;

class IpCannotBeLocatedException extends RuntimeException
{
    /** @var bool */
    private $isNonLocatableAddress;

    public function __construct(
        bool $isNonLocatableAddress,
        string $message,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->isNonLocatableAddress = $isNonLocatableAddress;
        parent::__construct($message, $code, $previous);
    }

    public static function forEmptyAddress(): self
    {
        return new self(true, 'Ignored visit with no IP address');
    }

    public static function forLocalhost(): self
    {
        return new self(true, 'Ignored localhost address');
    }

    public static function forError(Throwable $e): self
    {
        return new self(false, 'An error occurred while locating IP', $e->getCode(), $e);
    }

    /**
     * Tells if this error belongs to an address that will never be possible locate
     */
    public function isNonLocatableAddress(): bool
    {
        return $this->isNonLocatableAddress;
    }
}
