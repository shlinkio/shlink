<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

use Throwable;

/** @deprecated */
class AuthenticationException extends RuntimeException
{
    public static function expiredJWT(?Throwable $prev = null): self
    {
        return new self('The token has expired.', -1, $prev);
    }
}
