<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Util;

use Shlinkio\Shlink\Common\Exception as Common;
use Shlinkio\Shlink\Core\Exception as Core;
use Shlinkio\Shlink\Core\Exception\ShortUrlNotFoundException;
use Shlinkio\Shlink\Rest\Exception as Rest;
use Throwable;

class RestUtils
{
    /** @deprecated */
    public const INVALID_SHORTCODE_ERROR = ShortUrlNotFoundException::TYPE;
    // FIXME Should be INVALID_SHORT_URL_DELETION
    public const INVALID_SHORTCODE_DELETION_ERROR = 'INVALID_SHORTCODE_DELETION';
    public const INVALID_URL_ERROR = 'INVALID_URL';
    public const INVALID_ARGUMENT_ERROR = 'INVALID_ARGUMENT';
    public const INVALID_SLUG_ERROR = 'INVALID_SLUG';
    public const INVALID_CREDENTIALS_ERROR = 'INVALID_CREDENTIALS';
    public const INVALID_AUTH_TOKEN_ERROR = 'INVALID_AUTH_TOKEN';
    public const INVALID_AUTHORIZATION_ERROR = 'INVALID_AUTHORIZATION';
    public const INVALID_API_KEY_ERROR = 'INVALID_API_KEY';
    public const NOT_FOUND_ERROR = 'NOT_FOUND';
    public const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    /** @deprecated */
    public static function getRestErrorCodeFromException(Throwable $e): string
    {
        switch (true) {
            case $e instanceof Core\ShortUrlNotFoundException:
                return self::INVALID_SHORTCODE_ERROR;
            case $e instanceof Core\InvalidUrlException:
                return self::INVALID_URL_ERROR;
            case $e instanceof Core\NonUniqueSlugException:
                return self::INVALID_SLUG_ERROR;
            case $e instanceof Common\InvalidArgumentException:
            case $e instanceof Core\InvalidArgumentException:
            case $e instanceof Core\ValidationException:
                return self::INVALID_ARGUMENT_ERROR;
            case $e instanceof Rest\AuthenticationException:
                return self::INVALID_CREDENTIALS_ERROR;
            case $e instanceof Core\DeleteShortUrlException:
                return self::INVALID_SHORTCODE_DELETION_ERROR;
            default:
                return self::UNKNOWN_ERROR;
        }
    }
}
