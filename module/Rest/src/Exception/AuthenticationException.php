<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

class AuthenticationException extends RuntimeException
{
    public static function expiredJWT(\Exception $prev = null): self
    {
        return new self('The token has expired.', -1, $prev);
    }
}
