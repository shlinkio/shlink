<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;

class IpCannotBeLocatedException extends RuntimeException
{
    private bool $isNonLocatableAddress = true;

    public static function forEmptyAddress(): self
    {
        return new self('Ignored visit with no IP address');
    }

    public static function forLocalhost(): self
    {
        return new self('Ignored localhost address');
    }

    public static function forError(Throwable $e): self
    {
        $e = new self('An error occurred while locating IP', $e->getCode(), $e);
        $e->isNonLocatableAddress = false;

        return $e;
    }

    /**
     * Tells if this error belongs to an address that will never be possible locate
     */
    public function isNonLocatableAddress(): bool
    {
        return $this->isNonLocatableAddress;
    }
}
