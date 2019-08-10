<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\IpGeolocation\Exception;

use Shlinkio\Shlink\Common\Exception\RuntimeException;
use Throwable;

use function sprintf;

class WrongIpException extends RuntimeException implements ExceptionInterface
{
    public static function fromIpAddress($ipAddress, ?Throwable $prev = null): self
    {
        return new self(sprintf('Provided IP "%s" is invalid', $ipAddress), 0, $prev);
    }
}
