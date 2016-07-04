<?php
namespace Acelaya\UrlShortener\Util;

use Acelaya\UrlShortener\Exception;

class RestUtils
{
    const INVALID_SHORTCODE_ERROR = 'INVALID_SHORTCODE';
    const INVALID_URL_ERROR = 'INVALID_URL';
    const INVALID_ARGUMENT_ERROR = 'INVALID_ARGUMENT';
    const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public static function getRestErrorCodeFromException(Exception\ExceptionInterface $e)
    {
        switch (true) {
            case $e instanceof Exception\InvalidShortCodeException:
                return self::INVALID_SHORTCODE_ERROR;
            case $e instanceof Exception\InvalidUrlException:
                return self::INVALID_URL_ERROR;
            case $e instanceof Exception\InvalidArgumentException:
                return self::INVALID_ARGUMENT_ERROR;
            case $e instanceof Exception\AuthenticationException:
                return self::INVALID_CREDENTIALS;
            default:
                return self::UNKNOWN_ERROR;
        }
    }
}
