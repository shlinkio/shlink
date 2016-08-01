<?php
namespace Shlinkio\Shlink\Core\Exception;

use Shlinkio\Shlink\Common\Exception\RuntimeException;

class InvalidUrlException extends RuntimeException
{
    public static function fromUrl($url, \Exception $previous = null)
    {
        $code = isset($previous) ? $previous->getCode() : -1;
        return new static(sprintf('Provided URL "%s" is not an exisitng and valid URL', $url), $code, $previous);
    }
}
