<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ArrayUtils;

use function array_filter;
use function array_reduce;
use function in_array;

use const ARRAY_FILTER_USE_KEY;

function contains(mixed $value, array $array): bool
{
    return in_array($value, $array, strict: true);
}

/**
 * @param array[] $multiArray
 * @return array
 */
function flatten(array $multiArray): array
{
    return array_reduce(
        $multiArray,
        static fn (array $carry, array $value) => [...$carry, ...$value],
        initial: [],
    );
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
 * Returns an array containing only those entries in the array whose key is in the supplied keys.
 */
function select_keys(array $array, array $keys): array
{
    return array_filter(
        $array,
        static fn (string $key) => contains(
            $key,
            $keys,
        ),
        ARRAY_FILTER_USE_KEY,
    );
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
