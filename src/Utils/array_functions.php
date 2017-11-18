<?php

namespace League\Database\Utils;

/**
 * Determines if an array is associative.
 *
 * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
 *
 * @param  array  $array
 * @return bool
 */
function array_is_assoc(array $array)
{
    $keys = array_keys($array);

    return array_keys($keys) !== $keys;
}

/**
 * Get depth of array
 *
 * @param array $array
 *
 * @return int
 */
function array_depth(array $array)
{
    $maxDepth = 1;

    foreach ($array as $value) {
        if (is_array($value)) {
            $depth = array_depth($value) + 1;

            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }
    }

    return $maxDepth;
}

/**
 * Flatten a multi-dimensional array into a single level.
 *
 * @param  array  $array
 * @param  int  $depth
 * @return array
 */
function array_flatten(array $array, $depth = INF)
{
    return array_reduce($array, function ($result, $item) use ($depth) {
        if (!is_array($item)) {
            return array_merge($result, [$item]);
        } elseif ($depth === 1) {
            return array_merge($result, array_values($item));
        } else {
            return array_merge($result, array_flatten($item, $depth - 1));
        }
    }, []);
}

/**
 * Flatten a multi-dimensional array into a single level with saving keys
 *
 * @param  array  $array
 * @return array
 */
function array_flatten_assoc(array $array)
{
    $result = [];

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_flatten_assoc($value) + $result;
        } else {
            $result[$key] = $value;
        }
    }

    return $result;
}

/**
 * Check if array contains from specific types
 *
 * @param array  $array
 * @param string $type
 *
 * @return bool
 */
function is_array_of_type(array $array, string $type) : bool
{
    $types = [
        'boolean',
        'integer',
        'double',
        'string',
        'array',
        'object',
        'resource',
        'NULL',
    ];

    if (!in_array($type, $types)) {
        return false;
    }

    foreach ($array as $item) {
        if (gettype($item) !== $type) {
            return false;
        }
    }

    return true;
}
