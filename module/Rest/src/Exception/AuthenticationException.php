<?php
namespace Shlinkio\Shlink\Rest\Exception;

use Acelaya\UrlShortener\Exception\ExceptionInterface;

class AuthenticationException extends \RuntimeException implements ExceptionInterface
{
    public static function fromCredentials($username, $password)
    {
        return new self(sprintf('Invalid credentials. Username -> "%s". Password -> "%s"', $username, $password));
    }
}
