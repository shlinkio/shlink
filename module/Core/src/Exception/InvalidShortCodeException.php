<?php
namespace Shlinkio\Shlink\Core\Exception;

class InvalidShortCodeException extends RuntimeException
{
    public static function fromShortCode($shortCode, $charSet, \Exception $previous = null)
    {
        $code = isset($previous) ? $previous->getCode() : -1;
        return new static(
            sprintf('Provided short code "%s" does not match the char set "%s"', $shortCode, $charSet),
            $code,
            $previous
        );
    }
}
