<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ArrayUtils;

use function in_array;

/**
 * @template T
 * @param T $value
 * @param T[] $array
 */
function contains(mixed $value, array $array): bool
{
    return in_array($value, $array, strict: true);
}

/**
 * Checks if a callback returns true for at least one item in a collection.
 * @param callable(mixed $value, mixed $key): bool $callback
 */
function some(iterable $collection, callable $callback): bool
{
    foreach ($collection as $key => $value) {
        if ($callback($value, $key)) {
            return true;
        }
    }

    return false;
}

/**
 * Checks if a callback returns true for all item in a collection.
 * @param callable(mixed $value, string|number $key): bool $callback
 */
function every(iterable $collection, callable $callback): bool
{
    foreach ($collection as $key => $value) {
        if (! $callback($value, $key)) {
            return false;
        }
    }

    return true;
}

/**
 * @template T
 * @template R
 * @param iterable<T> $collection
 * @param callable(T $value, string|number $key): R $callback
 * @return R[]
 */
function map(iterable $collection, callable $callback): array
{
    $aggregation = [];
    foreach ($collection as $key => $value) {
        $aggregation[$key] = $callback($value, $key);
    }

    return $aggregation;
}
