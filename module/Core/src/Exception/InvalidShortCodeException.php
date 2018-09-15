<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

class InvalidShortCodeException extends RuntimeException
{
    public static function fromCharset($shortCode, $charSet, \Exception $previous = null)
    {
        $code = $previous !== null ? $previous->getCode() : -1;
        return new static(
            \sprintf('Provided short code "%s" does not match the char set "%s"', $shortCode, $charSet),
            $code,
            $previous
        );
    }

    public static function fromNotFoundShortCode($shortCode)
    {
        return new static(\sprintf('Provided short code "%s" does not belong to a short URL', $shortCode));
    }
}
