<?php

namespace Shlinkio\Shlink\Core\Util;

/**
 * To be used as the default value for arguments where we want to detect the param has not been explicitly provided,
 * where other typically "no value" values (like `null`) have their own meaning.
 *
 * An Enum is used so that there's a single instance, making it easier to perform comparison checks to narrow-down the
 * type.
 *
 * ```
 * function foo(int|null|NoValue $something = NoValue::NO_VALUE) { ... }
 *
 * foo();     - The value has not been provided
 * foo(null); - Null has been explicitly provided
 * foo(123);  - 123 has been explicitly provided
 * ```
 */
enum NoValue
{
    case NO_VALUE;

    /**
     * @template T
     * @param T|null $value
     * @return (T is NoValue::NO_VALUE ? null : T)
     */
    public static function resolve(mixed $value): mixed
    {
        return $value === NoValue::NO_VALUE ? null : $value;
    }
}
