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
 * Last modified: 2021.02.11 at 18:33
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Features;


use Neunerlei\Arrays\ArrayException;

abstract class Basics
{
    /**
     * Returns true if the given array is an associative array
     * Associative arrays have string keys instead of numbers!
     *
     * @param   array  $list  The array to check for
     *
     * @return bool
     */
    public static function isAssociative(array $list): bool
    {
        foreach (array_keys($list) as $k) {
            if (is_string($k)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the given array is sequential.
     * Sequential arrays are numeric and in order like 0 => 1, 1 => 2, 2 => 3.
     *
     * @param   array  $list  The array to check for
     *
     * @return bool
     */
    public static function isSequential(array $list): bool
    {
        return array_keys($list) === range(0, count($list) - 1);
    }

    /**
     * Returns true if the given array is a numeric list of arrays.
     * Meaning:
     *    $list = ["asdf" => 1] => FALSE
     *    $list = ["asdf" => ["asdf"]] => TRUE
     *    $list = [["asdf"], [123]] => TRUE
     *
     * @param   array  $list  The list to check
     *
     * @return bool
     */
    public static function isArrayList(array $list): bool
    {
        return count(array_filter($list, static function ($v) { return is_array($v); })) === count($list);
    }

    /**
     * Sorts the given list of strings by the length of the contained values
     *
     * @param   array  $list  The array of strings you want to sort
     * @param   bool   $asc   (FALSE) Set this to true if you want to sort ascending (shortest first)
     *
     * @return array
     */
    public static function sortByStrLen(array $list, bool $asc = false): array
    {
        uasort($list, static function ($a, $b) {
            return strlen((string)$b) - strlen((string)$a);
        });
        if ($asc) {
            $list = array_reverse($list, true);
        }

        return static::isAssociative($list) ? $list : array_values($list);
    }

    /**
     * Sorts the given list by the length of the key's strings
     * Similar to sortByStrLen() but sorts by key instead of the value
     *
     * @param   array  $list  The array of strings you want to sort
     * @param   bool   $asc   Default: False Set this to true if you want to sort ascending (shortest first)
     *
     * @return array
     */
    public static function sortByKeyStrLen(array $list, bool $asc = false): array
    {
        uksort($list, static function ($a, $b) {
            return strlen((string)$b) - strlen((string)$a);
        });
        if ($asc) {
            $list = array_reverse($list, true);
        }

        return $list;
    }

    /**
     * This method merges multiple arrays into each other. It will traverse elements recursively. While
     * traversing the second array ($b) all its values will be merged into the first array ($a). The values of $b will
     * overrule the values in $a. If both values are arrays the merge will go deeper and merge the child arrays into
     * each other.
     *
     * NOTE: By default numeric keys will be merged into each other so: [["foo"]] + [["bar"]] becomes [["bar"]].
     * This however is only the case for ARRAYS! All other values will be appended to $a, so ["a"] + ["b"] becomes
     * ["a", "b"]. You can use the "strictNumericMerge" and "noNumericMerge" flags to control the behaviour directly.
     *
     * NOTE2: It is possible to remove keys from an array while they are merge by using the __UNSET special value.
     * Keep in mind, that the "allowRemoval" flag has to be enabled for that.
     *
     * @param   array[]|string  ...$args  A list of arrays that should be merged with each other
     *                                    The list can contain the following strings to act as FLAGS to modify the
     *                                    behaviour of the method:
     *                                    - strictNumericMerge|sn: By default only arrays with numeric keys are merged
     *                                    into each other. By setting this flag ALL values will be merged into each
     *                                    other when they have numeric keys.
     *                                    - noNumericMerge|nn: Disables the merging of numeric keys. See NOTE above
     *                                    - allowRemoval|r: Enables the value "__UNSET" feature, which can be used in
     *                                    the merged array in order to unset array keys in the original array.
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public static function merge(...$args): array
    {
        // Prepare options
        $mergeNumeric       = true;
        $strictNumericMerge = false;
        $allowRemoval       = false;

        // Extract options and validate input
        $argsClean = [];
        foreach ($args as $k => $arg) {
            if (is_string($arg)) {
                $argLower = strtolower($arg);
                if ($argLower === 'nonumericmerge' || $argLower === 'nn') {
                    $mergeNumeric = false;
                    continue;
                }

                if ($argLower === 'allowremoval' || $argLower === 'r') {
                    $allowRemoval = true;
                    continue;
                }

                if ($argLower === 'strictnumericmerge' || $argLower === 'sn') {
                    $strictNumericMerge = true;
                    continue;
                }
            }
            if (! is_array($arg)) {
                throw new ArrayException('All elements have to be arrays! Element ' . $k . ' isn\'t!');
            }
            $argsClean[] = $arg;
        }
        $args = $argsClean;
        unset($argsClean);
        if (count($args) < 2) {
            throw new ArrayException('At least 2 elements are required to be merged into each other!');
        }


        // Loop over all given arguments
        $a = array_shift($args);
        while (count($args) > 0) {
            $a = static::mergeWalker($a, array_shift($args), $mergeNumeric, $strictNumericMerge, $allowRemoval);
        }

        return $a;
    }

    /**
     * Internal helper that is used to do the traverse two arrays recursively and merge them into each other
     *
     * @param   array  $a                   The array to merge $b into
     * @param   array  $b                   The array to merge into $a
     * @param   bool   $mergeNumeric        True to merge numeric keys into each other
     * @param   bool   $strictNumericMerge  Enables the strict numeric merge mode. Meaning all numeric values will be
     *                                      merged not only arrays
     * @param   bool   $allowRemoval        True to remove keys from $a that have a value of __UNSET in $b
     *
     * @return array
     */
    protected static function mergeWalker(
        array $a,
        array $b,
        bool $mergeNumeric,
        bool $strictNumericMerge,
        bool $allowRemoval
    ): array {
        if (! $allowRemoval && empty($a)) {
            return $b;
        }
        if (empty($b)) {
            return $a;
        }
        foreach ($b as $k => $v) {
            if ($allowRemoval && $v === '__UNSET') {
                unset($a[$k]);
                continue;
            }
            if (is_numeric($k) && ((! $strictNumericMerge && ! is_array($v)) || ! $mergeNumeric)) {
                $a[] = $v;
                continue;
            }
            if (isset($a[$k]) && is_array($a[$k]) && is_array($v)) {
                $v = static::mergeWalker($a[$k], $v, $mergeNumeric, $strictNumericMerge, $allowRemoval);
            }
            $a[$k] = $v;
        }

        return $a;
    }

    /**
     * This helper can be used to attach one array to the end of another.
     * This is basically [...] + [...] but without overriding numeric keys
     *
     * @param   array  $args
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public static function attach(...$args): array
    {
        if (count($args) < 2) {
            throw new ArrayException('At least 2 elements are required to be attached to eachother!');
        }
        if (in_array(false, array_map('is_array', $args), true)) {
            throw new ArrayException('All elements have to be arrays!');
        }

        $a = array_shift($args);
        while (count($args) > 0) {
            foreach (array_shift($args) as $k => $v) {
                if (is_numeric($k)) {
                    $a[] = $v;
                } else {
                    $a[$k] = $v;
                }
            }
        }

        return $a;
    }

    /**
     * This method can rename keys of a given array according to a given map
     * of ["keyToRename" => "RenamedKey"] as second parameter. Keys not present in $list will be ignored
     *
     * NOTE: Does NOT work with path's!
     *
     * @param   array  $list          The list to rename the keys in
     * @param   array  $keysToRename  The map to define which keys should be renamed with another key
     *
     * @return array The renamed array
     */
    public static function renameKeys(array $list, array $keysToRename): array
    {
        $result = [];
        foreach ($list as $k => $v) {
            $result[$keysToRename[$k] ?? $k] = $v;
        }

        return $result;
    }

    /**
     * Adds the given key ($insertKey) and value ($insertValue) pair either BEFORE or AFTER a $pivotKey in a $list.
     *
     * @param   array       $list          The list to add the new $insertValue to
     * @param   string|int  $pivotKey      The pivot key that is used as reference on where to insert the new value
     * @param   string|int  $insertKey     The new key to set for the given $insertValue
     * @param   mixed       $insertValue   The value to add to the given list
     * @param   bool        $insertBefore  By default the $insertValue is inserted AFTER the $pivotKey,
     *                                     set this to TRUE to insert it BEFORE the $pivotKey instead
     *
     * @return array
     */
    public static function insertAt(array $list, $pivotKey, $insertKey, $insertValue, bool $insertBefore = false): array
    {
        // Prepare the keys to be type-safe
        foreach ([&$pivotKey, &$insertKey] as &$val) {
            if (is_string($val) && is_numeric($val)
                && (string)(int)$val === (string)(float)$val) {
                $val = (int)$val;
            }
        }


        // Clean up
        unset($val, $list[$insertKey]);

        // Check if the pivot key exists
        if (! array_key_exists($pivotKey, $list)) {
            $list[$insertKey] = $insertValue;

            return $list;
        }

        // Position the new key around the the requested position
        $position        = array_search($pivotKey, array_keys($list), true);
        $before          = array_slice($list, 0, $position, true);
        $target          = [$pivotKey => $list[$pivotKey]];
        $after           = array_slice($list, $position + 1, null, true);
        $insertKeyIsNull = $insertKey === null;
        $insert          = $insertKeyIsNull ? [$insertValue] : [$insertKey => $insertValue];

        // Build the output
        if ($insertKeyIsNull) {
            return $insertBefore
                ? static::attach($before, $insert, $target, $after)
                : static::attach($before, $target, $insert, $after);
        }

        return $insertBefore
            ? $before + $insert + $target + $after
            : $before + $target + $insert + $after;
    }

    /**
     * Tiny helper which will shorten a multidimensional array until it's smallest element.
     * This is especially useful for database results.
     *
     * Example:
     * $a = array(
     *        "b" => array(
     *            "test" => 123
     *        )
     * )
     *
     * Result: 123
     *
     * @param   array  $array
     *
     * @return array|mixed
     */
    public static function shorten(array $array)
    {
        while (is_array($array) && count($array) === 1) {
            $array = reset($array);
        }

        return $array;
    }

    /**
     * Searches the most similar key to the given needle from the haystack
     *
     * @param   array       $haystack  The array to search similar keys in
     * @param   string|int  $needle    The needle to search similar keys for
     *
     * @return string|int|null The best matching key or null if the given haystack was empty
     */
    public static function getSimilarKey(array $haystack, $needle)
    {
        // Check if the needle exists
        if (isset($haystack[$needle])) {
            return $needle;
        }

        // Generate alternative keys
        $alternativeKeys = array_keys($haystack);
        $alternativeKeys = array_map(static function ($v) {
            return strtolower(trim((string)$v));
        }, array_combine($alternativeKeys, $alternativeKeys));

        // Search for a similar key
        $needlePrepared = strtolower(trim($needle));
        $similarKeys    = [];
        foreach ($alternativeKeys as $alternativeKey => $alternativeKeyPrepared) {
            similar_text($needlePrepared, $alternativeKeyPrepared, $percent);
            $similarKeys[(int)ceil($percent)] = $alternativeKey;
        }
        ksort($similarKeys);

        // Check for empty keys
        if (empty($similarKeys)) {
            return null;
        }

        return array_pop($similarKeys);
    }

    /**
     * Sorts a given multidimensional array by either a key or a path to a key, by keeping
     * the associative relations like asort would
     *
     * Example:
     * $a = array(
     *        'asdf' => array(
     *            'key' => 2,
     *            'sub' => array(
     *                'key' => 2
     *            )
     *        ),
     *        'cde' => array(
     *            'key' => 1,
     *            'sub' => array(
     *                'key' => 3
     *            )
     *        )
     * )
     *
     * // Keys in order
     * Arrays::sortBy($a, 'key') => cde, asdf
     * Arrays::sortBy($a, 'sub.key') => asdf, cde
     *
     * @param   array   $list     The array to sort
     * @param   string  $key      Either the key or the path to sort by
     * @param   array   $options  Additional config options:
     *                            - separator: (".") The separator between the parts if path's are used in $key
     *                            - desc: (FALSE) By default the method sorts ascending. To change to descending,
     *                            set this to true
     *
     * @return array
     */
    public static function sortBy(array $list, string $key, array $options = []): array
    {
        // Prepare Options
        $separator = isset($options['separator']) && is_string($options['separator']) ? $options['separator'] : '.';
        $desc      = array_key_exists('desc', $options) && is_bool($options['desc']) ? $options['desc'] : null;
        $desc      = ($desc === null && in_array('desc', $options, true));

        // Check if it is a simple sort => Use fastlane
        if (strpos($key, $separator) === false) {
            uasort($list, static function ($a, $b) use ($key) {
                return ($a[$key] ?? null) <=> ($b[$key] ?? null);
            });

            return $desc ? array_reverse($list) : $list;
        }

        // Use the workaround for paths as key
        // This is exorbitantly faster than using arrayGetPath in the approach above.
        // So this will combine the best of two worlds together
        $sorter = [];
        foreach ($list as $k => $v) {
            $sorter[$k] = static::getPath($list, $key, $separator);
        }
        asort($sorter);

        // Sort output
        $output = [];
        foreach ($sorter as $k => $foo) {
            $output[$k] = $list[$k];
        }
        unset($sorter);

        // Done
        return $desc ? array_reverse($output) : $output;
    }

    /**
     * Removes the given list of keys / paths from the $input array and returns the results
     *
     * @param   array  $list           The array to strip the unwanted fields from
     * @param   array  $pathsToRemove  The keys / paths to remove from $input
     * @param   array  $options        Additional config options
     *                                 - separator (".") Can be set to any string
     *                                 you want to use as separator of path parts.
     *                                 - removeEmpty (TRUE) Set this to false to disable
     *                                 the automatic cleanup of empty remains when the lowest
     *                                 child was removed from a tree.
     *
     * @return array
     */
    public static function without(array $list, array $pathsToRemove, array $options = []): array
    {
        foreach ($pathsToRemove as $path) {
            $list = static::removePath($list, $path, $options);
        }

        return $list;
    }

    /**
     * Flattens a multidimensional array into a one dimensional array, while keeping
     * their keys as "path". So for example:
     *
     * $array = ["foo" => 123, "bar" => ["baz" => 234]];
     * $arrayFlattened = ["foo" => 123, "bar.baz" => 234];
     *
     * @param   iterable  $list     The array or iterable to flatten
     * @param   array     $options  Additional config options:
     *                              - separator (string) default ".": Is used to define the separator
     *                              that glues the "key's" of the path together
     *                              - arraysOnly (bool) default FALSE: By default this method traverses
     *                              all kinds of iterable objects as well as arrays. If you only want
     *                              to traverse arrays set this to TRUE
     *
     * @return array
     */
    public static function flatten(iterable $list, array $options = []): array
    {
        // Prepare the options
        $separator  = isset($options['separator']) && is_string($options['separator'])
            ? $options['separator'] : '.';
        $arraysOnly = array_key_exists('arraysOnly', $options) && is_bool($options['arraysOnly'])
            ? $options['arraysOnly'] : null;
        $arraysOnly = ($arraysOnly === null && in_array('arraysOnly', $options, true));

        // Run the flattener
        $out = [];
        static::flattenWalker($out, $list, [], $separator, $arraysOnly);

        return $out;
    }

    /**
     * Internal helper to recursively iterate the given $input array and flatten it's contents into an array
     *
     * @param   array     $out
     * @param   iterable  $input
     * @param   array     $path
     * @param   string    $separator
     * @param   bool      $arraysOnly
     */
    protected static function flattenWalker(
        array &$out,
        iterable $input,
        array $path,
        string $separator,
        bool $arraysOnly
    ): void {
        foreach ($input as $k => $v) {
            $path[] = str_replace($separator, '\\' . $separator, $k);
            if (($arraysOnly && is_array($v)) || (! $arraysOnly && is_iterable($v))) {
                static::flattenWalker($out, $v, $path, $separator, $arraysOnly);
            } else {
                $out[implode($separator, $path)] = $v;
            }
            array_pop($path);
        }
    }

    /**
     * Basically the reverse operation of flatten()
     * Converts a flattened, one-dimensional array into a multidimensional array, using
     * their keys as "path". So for example:
     *
     * $arrayFlattened = ["foo" => 123, "bar.baz" => 234];
     * $array = ["foo" => 123, "bar" => ["baz" => 234]];
     *
     * @param   iterable  $list     The flattened list to inflate
     * @param   array     $options  Additional config options:
     *                              - separator (string) default ".": Is used to define the separator
     *                              that glues the "key's" of the path together
     *
     * @return array
     */
    public static function unflatten(iterable $list, array $options = []): array
    {
        // Prepare the options
        $separator = isset($options['separator']) && is_string($options['separator'])
            ? $options['separator'] : '.';

        $out = [];
        foreach ($list as $path => $value) {
            $out = static::setPath($out, $path, $value, $separator);
        }

        return $out;
    }

    /**
     * Works exactly like array_map but traverses the array recursively.
     *
     * You callback will get the following arguments:
     * $currentValue, $currentKey, $pathOfKeys, $inputArray
     *
     * @param   array     $list      The array to iterate
     * @param   callable  $callback  The callback to execute for every child of the given array.
     *
     * @return array
     */
    public static function mapRecursive(array $list, callable $callback): array
    {
        return static::mapRecursiveWalker($list, $list, [], $callback);
    }

    /**
     * Internal helper to recursively iterate over a given $list and execute a callback for ever child of it.
     *
     * @param   array     $list
     * @param   array     $currentList
     * @param   array     $path
     * @param   callable  $callback
     *
     * @return array
     */
    protected static function mapRecursiveWalker(
        array $list,
        array $currentList,
        array $path,
        callable $callback
    ): array {
        $output = [];
        foreach ($currentList as $k => $v) {
            $path[] = $k;
            if (is_array($v)) {
                $v = static::mapRecursiveWalker($list, $v, $path, $callback);
            } else {
                $v = $callback($v, $k, $path, $list);
            }
            $output[$k] = $v;
            array_pop($path);
        }

        return $output;
    }
}
