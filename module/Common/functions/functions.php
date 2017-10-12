<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

/**
 * Gets the value of an environment variable. Supports boolean, empty and null.
 * This is basically Laravel's env helper
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 * @link https://github.com/laravel/framework/blob/5.2/src/Illuminate/Foundation/helpers.php#L369
 */
function env($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }

    return trim($value);
}
