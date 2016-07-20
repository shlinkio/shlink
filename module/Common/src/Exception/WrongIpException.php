<?php
namespace Shlinkio\Shlink\Common\Exception;

class WrongIpException extends RuntimeException
{
    public static function fromIpAddress($ipAddress, \Exception $prev = null)
    {
        return new self(sprintf('Provided IP "%s" is invalid', $ipAddress), 0, $prev);
    }
}
