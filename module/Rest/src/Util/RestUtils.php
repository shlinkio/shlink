<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Util;

use Shlinkio\Shlink\Common\Exception as Common;
use Shlinkio\Shlink\Core\Exception as Core;
use Shlinkio\Shlink\Rest\Exception as Rest;
use Throwable;

class RestUtils
{
    /** @deprecated */
    public const INVALID_SHORTCODE_ERROR = Core\ShortUrlNotFoundException::TYPE;
    /** @deprecated */
    public const INVALID_SHORTCODE_DELETION_ERROR = Core\DeleteShortUrlException::TYPE;
    /** @deprecated */
    public const INVALID_URL_ERROR = Core\InvalidUrlException::TYPE;
    /** @deprecated */
    public const INVALID_ARGUMENT_ERROR = Core\ValidationException::TYPE;
    /** @deprecated */
    public const INVALID_SLUG_ERROR = Core\NonUniqueSlugException::TYPE;
    /** @deprecated */
    public const NOT_FOUND_ERROR = Core\TagNotFoundException::TYPE;
    /** @deprecated */
    public const UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    public const INVALID_CREDENTIALS_ERROR = 'INVALID_CREDENTIALS';
    public const INVALID_AUTH_TOKEN_ERROR = 'INVALID_AUTH_TOKEN';
    public const INVALID_AUTHORIZATION_ERROR = 'INVALID_AUTHORIZATION';
    public const INVALID_API_KEY_ERROR = 'INVALID_API_KEY';

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
