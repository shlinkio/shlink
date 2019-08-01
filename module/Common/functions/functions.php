<?php
declare(strict_types=1);

namespace Shlinkio\Shlink\Common;

use function getenv;
use function json_decode as spl_json_decode;
use function json_last_error;
use function json_last_error_msg;
use function sprintf;
use function strtolower;
use function trim;

use const JSON_ERROR_NONE;

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

/**
 * @throws Exception\InvalidArgumentException
 */
function json_decode(string $json, int $depth = 512, int $options = 0): array
{
    $data = spl_json_decode($json, true, $depth, $options);
    if (JSON_ERROR_NONE !== json_last_error()) {
        throw new Exception\InvalidArgumentException(sprintf('Error decoding JSON: %s', json_last_error_msg()));
    }

    return $data;
}
