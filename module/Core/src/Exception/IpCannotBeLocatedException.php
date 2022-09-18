<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Shlinkio\Shlink\Core\Visit\Model\UnlocatableIpType;
use Throwable;

class IpCannotBeLocatedException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly UnlocatableIpType $type,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function forEmptyAddress(): self
    {
        return new self('Ignored visit with no IP address', UnlocatableIpType::EMPTY_ADDRESS);
    }

    public static function forLocalhost(): self
    {
        return new self('Ignored localhost address', UnlocatableIpType::LOCALHOST);
    }

    public static function forError(Throwable $e): self
    {
        return new self('An error occurred while locating IP', UnlocatableIpType::ERROR, $e->getCode(), $e);
    }

    /**
     * Tells if this belongs to an address that will never be possible to locate
     */
    public function isNonLocatableAddress(): bool
    {
        return $this->type !== UnlocatableIpType::ERROR;
    }
}
