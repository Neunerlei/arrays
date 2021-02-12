<?php
/*
 * Copyright 2021 LABOR.digital
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2021.02.11 at 18:32
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Traits;


use Neunerlei\Arrays\Arrays;
use RuntimeException;
use Throwable;

trait PathTrait
{
    use PathParsingTrait;

    /**
     * This method can be used to merge two paths together.
     * This becomes useful if you want to work with a dynamic part in form of an array
     * and a static string part. The result will always be a path array.
     * You can specify a separator type for each part of the given path if you merge
     * differently formatted paths.
     *
     * It merges stuff like:
     *        - "a.path.to." and ["parts","inTheTree"] => ["a", "path", "to", "parts", "inTheTree"]
     *        - "a.b.*" and "c.d.[asdf,id]" => ["a", "b", "*", "c", "d", ["asdf", "id"]
     *        - "a.b" and "c,d" => ["a","b","c","d"] (If $separatorB is set to ",")
     * and so on...
     *
     * @param   array|string  $pathA       The path to add $pathB to
     * @param   array|string  $pathB       The path to be added to $pathA
     * @param   string|null   $separatorA  The separator for string paths in $pathA
     * @param   string|null   $separatorB  The separator for string paths in $pathB
     *
     * @return array
     */
    public static function mergePaths($pathA, $pathB, ?string $separatorA = null, ?string $separatorB = null): array
    {
        $separatorB = $separatorB ?? $separatorA;
        $pathA      = static::parsePath($pathA, $separatorA, true);
        $pathB      = static::parsePath($pathB, $separatorB, true);
        foreach ($pathB as $p) {
            $pathA[] = $p;
        }

        return $pathA;
    }

    /**
     * This method checks if a given path exists in a given $input array
     *
     * @param   array         $list       The array to check
     * @param   array|string  $path       The path to check for in $input
     * @param   string|null   $separator  "." Can be set to any string you want to use as separator of path parts.
     *
     * @return bool
     */
    public static function hasPath(array $list, $path, ?string $separator = null): bool
    {
        $separator = $separator ?? static::DEFAULT_PATH_SEPARATOR;

        if (static::canUseFastLane($path, $separator)) {
            return array_key_exists($path, $list);
        }

        $path = static::parsePath($path, $separator);

        try {
            static::hasPathWalker($list, $path);
        } catch (Throwable $e) {
            return false;
        }

        return true;
    }

    /**
     * Internal walker method for "hasPath"
     *
     * @param   array  $list
     * @param   array  $path
     *
     * @throws \RuntimeException
     * @see hasPath()
     */
    protected static function hasPathWalker(array $list, array $path): void
    {
        [$keys, $isLastKey] = static::initWalkerStep($list, $path);

        if (empty($list) || empty($keys)) {
            throw new RuntimeException('Given list or keys are empty');
        }

        foreach ($keys as $key) {
            // Handle nested paths
            if (is_array($key)) {
                static::hasPathWalker($list, $key);
                continue;
            }

            if (! array_key_exists($key, $list)) {
                throw new RuntimeException('Key not found');
            }

            if (! $isLastKey) {
                if (! is_array($list[$key])) {
                    throw new RuntimeException('Child not found');
                }

                static::hasPathWalker($list[$key], $path);
            }
        }
    }

    /**
     * This method reads a single value or multiple values (depending on the given $path) from
     * the given $input array.
     *
     * @param   array         $list       The array to read the path's values from
     * @param   array|string  $path       The path to read in the $input array
     * @param   null|mixed    $default    The value which will be returned if the $path did not match anything.
     * @param   string|null   $separator  "." Can be set to any string you want to use as separator of path parts.
     *
     * @return array|mixed|null
     */
    public static function getPath(array $list, $path, $default = null, ?string $separator = null)
    {
        if (empty($list)) {
            return $default;
        }

        $separator = $separator ?? static::DEFAULT_PATH_SEPARATOR;

        if (static::canUseFastLane($path, $separator)) {
            return array_key_exists($path, $list) ? $list[$path] : $default;
        }

        return static::getPathWalker($list, static::parsePath($path, $separator), $default);
    }

    /**
     * Internal walker method for "getPath"
     *
     * @param   array  $list
     * @param   array  $path
     * @param          $default
     * @param   bool   $isNested
     *
     * @return array|mixed
     * @see getPath
     */
    protected static function getPathWalker(array $list, array $path, $default, bool $isNested = false)
    {
        [$keys, $isLastKey, $keyType] = static::initWalkerStep($list, $path);

        $result = [];

        foreach ($keys as $key) {
            if (is_array($key)) {
                $result = Arrays::merge($result, static::getPathWalker($list, $key, $default, true));
                continue;
            }

            if (! array_key_exists($key, $list)) {
                $result[$key] = $default;
                continue;
            }

            if ($isLastKey) {
                $result[$key] = $list[$key];

            } else {
                if (! is_array($list[$key])) {
                    $result[$key] = $default;
                    continue;
                }

                $result[$key] = static::getPathWalker($list[$key], $path, $default, $isNested);
            }
        }

        if ($isNested === true) {
            return $result;
        }

        if ($keyType === static::KEY_TYPE_DEFAULT) {
            return $result[key($result)];
        }

        if ($keyType === static::KEY_TYPE_WILDCARD && reset($keys) === 0) {
            return array_values($result);
        }

        return $result;
    }

    /**
     * This method lets you set a given value at a path of your array.
     * You can also set multiple keys to the same value at once if you use wildcards.
     *
     * @param   array         $list       The array to set the values in
     * @param   array|string  $path       The path to set $value at
     * @param   mixed         $value      The value to set at $path in $input
     * @param   string        $separator  "." Can be set to any string you want to use as separator of path parts.
     *
     * @return array
     */
    public static function setPath(array $list, $path, $value, string $separator = '.'): array
    {
        if (static::canUseFastLane($path, $separator)) {
            $list[$path] = $value;

            return $list;
        }

        static::setPathWalker($list, static::parsePath($path, $separator), $value);

        return $list;
    }

    /**
     * Internal walker method for "setPath"
     *
     * @param   array  $list
     * @param   array  $path
     * @param          $value
     *
     * @see setPath()
     */
    protected static function setPathWalker(array &$list, array $path, $value): void
    {
        [$keys, $isLastKey] = static::initWalkerStep($list, $path);
        foreach ($keys as $key) {
            if (is_array($key)) {
                static::setPathWalker($list, $key, $value);
                continue;
            }

            if ($isLastKey) {
                $list[$key] = $value;
                continue;
            }

            if (! array_key_exists($key, $list) || ! is_array($list[$key])) {
                $list[$key] = [];
            }

            static::setPathWalker($list[$key], $path, $value);
        }
    }

    /**
     * Removes the values at the given $path"s from the $input array.
     * It can also remove multiple values at once if you use wildcards.
     *
     * NOTE: The method tries to remove empty remains recursively when the last
     * child was removed from the branch. If you don"t want to use this behaviour
     * set $removeEmptyRemains to false.
     *
     * @param   array         $list            The array to remove the values from
     * @param   array|string  $path            The path which defines which values have to be removed
     * @param   array         $options         Additional config options
     *                                         - separator (string) ".": Can be set to any string
     *                                         you want to use as separator of path parts.
     *                                         - keepEmpty (bool) TRUE: Set this to false to disable
     *                                         the automatic cleanup of empty remains when the lowest
     *                                         child was removed from a tree.
     *
     * @return array
     */
    public static function removePath(array $list, $path, array $options = []): array
    {
        $separator = $options['separator'] ?? static::DEFAULT_PATH_SEPARATOR;

        if (static::canUseFastLane($path, $separator)) {
            unset($list[$path]);

            return $list;
        }

        $keepEmpty = array_key_exists('keepEmpty', $options) && is_bool($options['keepEmpty'])
            ? $options['keepEmpty'] : null;
        $keepEmpty = ($keepEmpty === null && in_array('keepEmpty', $options, true));

        static::removePathWalker($list, static::parsePath($path, $separator), $keepEmpty);

        return $list;
    }

    /**
     * Internal walker method for "removePath"
     *
     * @param   array  $list
     * @param   array  $path
     * @param   bool   $keepEmpty
     *
     * @see removePath()
     */
    protected static function removePathWalker(array &$list, array $path, bool $keepEmpty): void
    {
        [$keys, $isLastKey] = static::initWalkerStep($list, $path);
        foreach ($keys as $key) {
            if (is_array($key)) {
                static::removePathWalker($list, $key, $keepEmpty);
                continue;
            }

            if ($isLastKey) {
                unset($list[$key]);
                continue;
            }

            if (is_array($list[$key])) {
                static::removePathWalker($list[$key], $path, $keepEmpty);
            }

            if (! $keepEmpty && empty($list[$key])) {
                unset($list[$key]);
            }
        }
    }

    /**
     * This method can be used to apply a filter to all values the given $path matches.
     * The given $callback will receive the following parameters:
     * $path: "the.path.trough.your.array" to let you decide how to handle the current value
     * $value: The reference of the current $input's value. Change this value to change $input correspondingly.
     * The callback should always return void.
     *
     * @param   array         $list       The array to filter
     * @param   array|string  $path       The path which defines the values to filter
     * @param   callable      $callback   The callback to trigger on every value found by $path
     * @param   string|null   $separator  "." Can be set to any string you want to use as separator of path parts.
     *
     * @return array
     */
    public static function filterPath(array $list, $path, callable $callback, ?string $separator = null): array
    {
        static::filterPathWalker($list, static::parsePath($path, $separator), [], $callback, $list);

        return $list;
    }

    /**
     * Internal walker method for "filter"
     *
     * @param   array     $list
     * @param   array     $path
     * @param   array     $localPath
     * @param   callable  $callback
     * @param   array     $inputArray
     *
     * @see filter()
     */
    protected static function filterPathWalker(
        array &$list,
        array $path,
        array $localPath,
        callable $callback,
        array $inputArray
    ): void {
        [$keys, $isLastKey] = static::initWalkerStep($list, $path);

        foreach ($keys as $key) {
            $localPath[] = $key;

            if (is_array($key)) {
                static::filterPathWalker($list, $key, $localPath, $callback, $inputArray);
            } elseif ($isLastKey) {
                $list[$key] = $callback($list[$key], $key, $localPath, $inputArray);
            } elseif (is_array($list[$key])) {
                static::filterPathWalker($list[$key], $path, $localPath, $callback, $inputArray);
            }

            array_pop($localPath);
        }
    }

    /**
     * Internal helper which decides if a path can use the "fast-lane" resolution or not
     *
     * @param   array|string  $path       The path to check
     * @param   string        $separator  The path separator character
     *
     * @return bool
     */
    protected static function canUseFastLane($path, string $separator): bool
    {
        if (! is_string($path) || empty($path)) {
            return false;
        }

        if (stripos($path, $separator) !== false) {
            return false;
        }

        foreach (static::ESCAPABLE_CHARS as $escapable) {
            if (stripos($path, $escapable) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Internal helper which is used to parse the current path into a list of control variables
     *
     * @param   array  $input
     * @param   array  $path
     *
     * @return array
     */
    protected static function initWalkerStep(array $input, array &$path): array
    {
        // Prepare result
        $part      = array_shift($path);
        $keyType   = static::KEY_TYPE_DEFAULT;
        $isLastKey = empty($path);

        // Handle incoming array -> SubKeys
        if (is_array($part)) {
            $keyType = self::KEY_TYPE_KEYS;
            $keys    = $part;
        } else {
            $key = $part;
            // Get the type of the current key
            if ($key === '*') {
                // WILDCARD
                $keyType = self::KEY_TYPE_WILDCARD;
                $keys    = array_keys($input);
            } else {
                $keys = [$key];
            }
        }

        return [$keys, $isLastKey, $keyType];
    }
}
