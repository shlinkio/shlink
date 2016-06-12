<?php
namespace Acelaya\UrlShortener\Util;

use Acelaya\UrlShortener\Exception\ExceptionInterface;
use Acelaya\UrlShortener\Exception\InvalidShortCodeException;
use Acelaya\UrlShortener\Exception\InvalidUrlException;

class RestUtils
{
    const INVALID_SHORTCODE_ERROR = 'INVALID_SHORTCODE';
    const INVALID_URL_ERROR = 'INVALID_URL';
    const INVALID_ARGUMENT_ERROR = 'INVALID_ARGUMEN';
    const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public static function getRestErrorCodeFromException(ExceptionInterface $e)
    {
        switch (true) {
            case $e instanceof InvalidShortCodeException:
                return self::INVALID_SHORTCODE_ERROR;
            case $e instanceof InvalidUrlException:
                return self::INVALID_URL_ERROR;
            default:
                return self::UNKNOWN_ERROR;
        }
    }
}
