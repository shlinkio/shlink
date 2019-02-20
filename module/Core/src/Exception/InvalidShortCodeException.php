<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use Throwable;
use function sprintf;

class InvalidShortCodeException extends RuntimeException
{
    public static function fromCharset(string $shortCode, string $charSet, ?Throwable $previous = null): self
    {
        $code = $previous !== null ? $previous->getCode() : -1;
        return new static(
            sprintf('Provided short code "%s" does not match the char set "%s"', $shortCode, $charSet),
            $code,
            $previous
        );
    }

    public static function fromNotFoundShortCode(string $shortCode): self
    {
        return new static(sprintf('Provided short code "%s" does not belong to a short URL', $shortCode));
    }
}
