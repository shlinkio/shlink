<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

class InvalidUrlException extends RuntimeException
{
    public static function fromUrl($url, \Throwable $previous = null)
    {
        $code = isset($previous) ? $previous->getCode() : -1;
        return new static(sprintf('Provided URL "%s" is not an existing and valid URL', $url), $code, $previous);
    }
}
