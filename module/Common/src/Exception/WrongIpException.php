<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common\Exception;

use function sprintf;

class WrongIpException extends RuntimeException
{
    public static function fromIpAddress($ipAddress, \Throwable $prev = null): self
    {
        return new self(sprintf('Provided IP "%s" is invalid', $ipAddress), 0, $prev);
    }
}
