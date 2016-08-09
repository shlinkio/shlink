<?php
namespace Shlinkio\Shlink\Rest\Exception;

use Shlinkio\Shlink\Common\Exception\ExceptionInterface;

class AuthenticationException extends \RuntimeException implements ExceptionInterface
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
