<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Exception;

class AuthenticationException extends RuntimeException
{
    public static function fromCredentials($username, $password)
    {
        return new self(sprintf('Invalid credentials. Username -> "%s". Password -> "%s"', $username, $password));
    }

    public static function expiredJWT(\Exception $prev = null)
    {
        return new self('The token has expired.', -1, $prev);
    }
}
