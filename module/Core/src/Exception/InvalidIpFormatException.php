<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Exception;

use function sprintf;

class InvalidIpFormatException extends RuntimeException implements ExceptionInterface
{
    public static function fromInvalidIp(string $ipAddress): self
    {
        return new self(sprintf('Provided IP %s does not have the right format. Expected X.X.X.X', $ipAddress));
    }
}
