<?php
/**
 * Copyright 2020 Martin Neundorfer (Neunerlei)
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
 * Last modified: 2020.02.27 at 10:57
 */
declare(strict_types=1);

namespace Neunerlei\Arrays;


use Neunerlei\Options\Options;

class Arrays {
	
	/**
	 * The class that is used to dump arrays into serialized variants
	 * @var string
	 */
	public static $dumperClass = ArrayDumper::class;
	
	/**
	 * The class used to generate arrays from other data types
	 * @var string
	 */
	public static $generatorClass = ArrayGenerator::class;
	
	/**
	 * The class used to perform path actions in arrays
	 * @var string
	 */
	public static $pathClass = ArrayPaths::class;
	
	/**
	 * The list of instances that are already created
	 * @var array
	 */
	protected static $instances = [];
	
	/**
	 * Returns true if the given array is an associative array
	 * Associative arrays have string keys instead of numbers!
	 *
	 * @param array $list The array to check for
	 *
	 * @return bool
	 */
	public static function isAssociative(array $list): bool {
		foreach (array_keys($list) as $k)
			if (is_string($k)) return TRUE;
		return FALSE;
	}
	
	/**
	 * Returns true if the given array is sequential.
	 * Sequential arrays are numeric and in order like 0 => 1, 1 => 2, 2 => 3.
	 *
	 * @param array $list The array to check for
	 *
	 * @return bool
	 */
	public static function isSequential(array $list): bool {
		return array_keys($list) === range(0, count($list) - 1);
	}
	
	/**
	 * Returns true if the given array is a numeric list of arrays.
	 * Meaning:
	 *    $list = ["asdf" => 1] => FALSE
	 *    $list = ["asdf" => ["asdf"]] => TRUE
	 *    $list = [["asdf"], [123]] => TRUE
	 *
	 * @param array $list The list to check
	 *
	 * @return bool
	 */
	public static function isArrayList(array $list): bool {
		return count(array_filter($list, function ($v) { return is_array($v); })) === count($list);
	}
	
	/**
	 * Sorts the given list of strings by the length of the contained values
	 *
	 * @param array $list The array of strings you want to sort
	 * @param bool  $asc  (FALSE) Set this to true if you want to sort ascending (shortest first)
	 *
	 * @return array
	 */
	public static function sortByStrLen(array $list, bool $asc = FALSE): array {
		uasort($list, function ($a, $b) {
			return strlen((string)$b) - strlen((string)$a);
		});
		if ($asc) $list = array_reverse($list, TRUE);
		return static::isAssociative($list) ? $list : array_values($list);
	}
	
	/**
	 * Sorts the given list by the length of the key's strings
	 * Similar to sortByStrLen() but sorts by key instead of the value
	 *
	 * @param array $list The array of strings you want to sort
	 * @param bool  $asc  Default: False Set this to true if you want to sort ascending (shortest first)
	 *
	 * @return array
	 */
	public static function sortByKeyStrLen(array $list, bool $asc = FALSE): array {
		uksort($list, function ($a, $b) {
			return strlen((string)$b) - strlen((string)$a);
		});
		if ($asc) $list = array_reverse($list, TRUE);
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
	 * @param array[] ...$args   A list of arrays that should be merged with each other
	 *                           The list can contain the following strings to act as FLAGS to modify the behaviour of
	 *                           the method:
	 *                           - strictNumericMerge|sn: By default only arrays with numeric keys are merged into each
	 *                           other. By setting this flag ALL values will be merged into each other when they have
	 *                           numeric keys.
	 *                           - noNumericMerge|nn: Disables the merging of numeric keys. See NOTE above
	 *                           - allowRemoval|r: Enables the value "__UNSET" feature, which can be used in the merged
	 *                           array in order to unset array keys in the original array.
	 *
	 * @return array
	 * @throws \Neunerlei\Arrays\ArrayException
	 */
	public static function merge(...$args): array {
		// Prepare options
		$mergeNumeric = TRUE;
		$strictNumericMerge = FALSE;
		$allowRemoval = FALSE;
		
		// Extract options and validate input
		$argsClean = [];
		foreach ($args as $k => $arg) {
			if (is_string($arg)) {
				$argLower = strtolower($arg);
				if ($argLower === "nonumericmerge" || $argLower === "nn") {
					$mergeNumeric = FALSE;
					continue;
				} else if ($argLower === "allowremoval" || $argLower === "r") {
					$allowRemoval = TRUE;
					continue;
				} else if ($argLower === "strictnumericmerge" || $argLower === "sn") {
					$strictNumericMerge = TRUE;
					continue;
				}
			}
			if (!is_array($arg))
				throw new ArrayException("All elements have to be arrays! Element $k isn't!");
			$argsClean[] = $arg;
		}
		$args = $argsClean;
		unset($argsClean);
		if (count($args) < 2)
			throw new ArrayException("At least 2 elements are required to be merged into eachother!");
		
		
		// Loop over all given arguments
		$a = array_shift($args);
		while (count($args) > 0)
			$a = static::mergeWalker($a, array_shift($args), $mergeNumeric, $strictNumericMerge, $allowRemoval);
		return $a;
	}
	
	/**
	 * Internal helper that is used to do the traverse two arrays recursively and merge them into each other
	 *
	 * @param array $a                  The array to merge $b into
	 * @param array $b                  The array to merge into $a
	 * @param bool  $mergeNumeric       True to merge numeric keys into each other
	 * @param bool  $strictNumericMerge Enables the strict numeric merge mode. Meaning all numeric values will be
	 *                                  merged not only arrays
	 * @param bool  $allowRemoval       True to remove keys from $a that have a value of __UNSET in $b
	 *
	 * @return array
	 */
	protected static function mergeWalker(array $a, array $b, bool $mergeNumeric, bool $strictNumericMerge, bool $allowRemoval): array {
		if (empty($a)) return $b;
		if (empty($b)) return $a;
		foreach ($b as $k => $v) {
			if ($allowRemoval && $v === "__UNSET") {
				unset($a[$k]);
				continue;
			}
			if (is_numeric($k) && (!$strictNumericMerge && !is_array($v) || !$mergeNumeric)) {
				$a[] = $v;
				continue;
			}
			if (isset($a[$k]) && is_array($a[$k]) && is_array($v))
				$v = static::mergeWalker($a[$k], $v, $mergeNumeric, $strictNumericMerge, $allowRemoval);
			$a[$k] = $v;
		}
		return $a;
	}
	
	/**
	 * This helper can be used to attach one array to the end of another.
	 * This is basically [...] + [...] but without overriding numeric keys
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws \Neunerlei\Arrays\ArrayException
	 */
	public static function attach(...$args): array {
		$_args = $args;
		if (count($args) < 2)
			throw new ArrayException("At least 2 elements are required to be attached to eachother!");
		if (in_array(FALSE, array_map("is_array", $_args)))
			throw new ArrayException("All elements have to be arrays!");
		
		$a = array_shift($args);
		while (count($args) > 0) {
			foreach (array_shift($args) as $k => $v) {
				if (!is_numeric($k)) $a[$k] = $v;
				else $a[] = $v;
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
	 * @param array $list         The list to rename the keys in
	 * @param array $keysToRename The map to define which keys should be renamed with another key
	 *
	 * @return array The renamed array
	 */
	public static function renameKeys(array $list, array $keysToRename): array {
		$result = [];
		foreach ($list as $k => $v)
			$result[isset($keysToRename[$k]) ? $keysToRename[$k] : $k] = $v;
		return $result;
	}
	
	/**
	 * Adds the given key ($insertKey) and value ($insertValue) pair either BEFORE or AFTER a $pivotKey in a $list.
	 *
	 * @param array      $list         The list to add the new $insertValue to
	 * @param string|int $pivotKey     The pivot key that is used as reference on where to insert the new value
	 * @param string|int $insertKey    The new key to set for the given $insertValue
	 * @param mixed      $insertValue  The value to add to the given list
	 * @param bool       $insertBefore By default the $insertValue is inserted AFTER the $pivotKey,
	 *                                 set this to TRUE to insert it BEFORE the $pivotKey instead
	 *
	 * @return array
	 */
	public static function insertAt(array $list, $pivotKey, $insertKey, $insertValue, bool $insertBefore = FALSE): array {
		// Remove the existing key
		unset($list[$insertKey]);
		
		// Check if the pivot key exists
		if (!array_key_exists($pivotKey, $list)) {
			$list[$insertKey] = $insertValue;
			return $list;
		}
		
		// Position the new key around the the requested position
		$position = array_search($pivotKey, array_keys($list));
		$before = array_slice($list, 0, $position, TRUE);
		$target = [$pivotKey => $list[$pivotKey]];
		$after = array_slice($list, $position + 1, NULL, TRUE);
		$insertKeyIsNull = is_null($insertKey);
		$insert = $insertKeyIsNull ? [$insertValue] : [$insertKey => $insertValue];
		
		// Build the output
		if ($insertKeyIsNull)
			return $insertBefore ? Arrays::attach($before, $insert, $target, $after) :
				Arrays::attach($before, $target, $insert, $after);
		return $insertBefore ?
			$before + $insert + $target + $after :
			$before + $target + $insert + $after;
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
	 * @param array $array
	 *
	 * @return array|mixed
	 */
	public static function shorten(array $array) {
		while (is_array($array) && count($array) === 1)
			$array = reset($array);
		return $array;
	}
	
	/**
	 * Searches the most similar key to the given needle from the haystack
	 *
	 * @param array  $haystack The array to search similar keys in
	 * @param string $needle   The needle to search similar keys for
	 *
	 * @return string|null The best matching key or null if the given haystack was empty
	 */
	public static function getSimilarKey(array $haystack, $needle) {
		// Check if the needle exists
		if (isset($haystack[$needle])) return $needle;
		
		// Generate alternative keys
		$alternativeKeys = array_keys($haystack);
		$alternativeKeys = array_map(function ($v) {
			return trim(strtolower((string)$v));
		}, array_combine($alternativeKeys, $alternativeKeys));
		
		// Search for a similar key
		$needlePrepared = trim(strtolower($needle));
		$similarKeys = [];
		foreach ($alternativeKeys as $alternativeKey => $alternativeKeyPrepared) {
			similar_text($needlePrepared, $alternativeKeyPrepared, $percent);
			$similarKeys[(int)ceil($percent)] = $alternativeKey;
		}
		ksort($similarKeys);
		
		// Check for empty keys
		if (empty($similarKeys)) return NULL;
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
	 * @param array  $list    The array to sort
	 * @param string $key     Either the key or the path to sort by
	 * @param array  $options Additional config options:
	 *                        - separator: (".") The separator between the parts if path's are used in $key
	 *                        - desc: (FALSE) By default the method sorts ascending. To change to descending,
	 *                        set this to true
	 *
	 * @return array
	 * @throws \Neunerlei\Arrays\ArrayException
	 */
	public static function sortBy(array $list, string $key, array $options = []): array {
		$options = Options::make($options, [
			"separator" => [
				"type"    => "string",
				"default" => ".",
			],
			"desc"      => [
				"type"    => "bool",
				"default" => FALSE,
			],
		]);
		
		// Check if it is a simple sort => Use fastlane
		if (stripos($key, $options["separator"]) === FALSE) {
			uasort($list, function ($a, $b) use ($key) {
				return (isset($a[$key]) ? $a[$key] : NULL) <=> (isset($b[$key]) ? $b[$key] : NULL);
			});
			return $options["desc"] ? array_reverse($list) : $list;
		}
		
		// Use the workaround for paths as key
		// This is exorbitantly faster than using arrayGetPath in the approach above.
		// So this will combine the best of two worlds together
		$sorter = [];
		foreach ($list as $k => $v)
			$sorter[$k] = Arrays::getPath($list, $key, $options["separator"]);
		asort($sorter);
		
		// Sort output
		$output = [];
		foreach ($sorter as $k => $foo)
			$output[$k] = $list[$k];
		unset($sorter);
		
		// Done
		return $options["desc"] ? array_reverse($output) : $output;
	}
	
	/**
	 * This method is used to convert a string into a path array.
	 * It will also validate already existing path arrays.
	 *
	 * By default a period (.) is used to separate path parts like: "my.array.path" => ["my","array","path"].
	 * If you require another separator you can set another one by using the $separator parameter.
	 * In most circumstances it will make more sense just to escape a separator, tho. Do that by using a backslash like:
	 * "my\.array.path" => ["my.array", "path"].
	 *
	 * @param array|string $path      The path to parse as described above.
	 * @param string       $separator "." Can be set to any string you want to use as separator of path parts.
	 *
	 * @return array
	 */
	public static function parsePath($path, string $separator = "."): array {
		return static::getInstance(static::$pathClass)->parsePath($path, $separator);
	}
	
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
	 * @param array|string $pathA      The path to add $pathB to
	 * @param array|string $pathB      The path to be added to $pathA
	 * @param string       $separatorA The separator for string paths in $pathA
	 * @param string       $separatorB The separator for string paths in $pathB
	 *
	 * @return array
	 */
	public static function mergePaths($pathA, $pathB, ?string $separatorA = ".", ?string $separatorB = NULL): array {
		return static::getInstance(static::$pathClass)
			->mergePaths($pathA, $pathB, $separatorA, $separatorB === NULL ? $separatorA : $separatorB);
	}
	
	/**
	 * This method checks if a given path exists in a given $input array
	 *
	 * @param array|mixed  $input     The array to check
	 * @param array|string $path      The path to check for in $input
	 * @param string       $separator "." Can be set to any string you want to use as separator of path parts.
	 *
	 * @return bool
	 */
	public static function hasPath($input, $path, string $separator = "."): bool {
		return static::getInstance(static::$pathClass)->has($input, $path, $separator);
	}
	
	/**
	 * This method reads a single value or multiple values (depending on the given $path) from
	 * the given $input array.
	 *
	 * @param array        $input     The array to read the path's values from
	 * @param array|string $path      The path to read in the $input array
	 * @param null|mixed   $default   The value which will be returned if the $path did not match anything.
	 * @param string       $separator "." Can be set to any string you want to use as separator of path parts.
	 *
	 * @return array|mixed|null
	 */
	public static function getPath(array $input, $path, $default = NULL, string $separator = ".") {
		return static::getInstance(static::$pathClass)->get($input, $path, $default, $separator);
	}
	
	/**
	 * This method lets you set a given value at a path of your array.
	 * You can also set multiple keys to the same value at once if you use wildcards.
	 *
	 * @param array        $input     The array to set the values in
	 * @param array|string $path      The path to set $value at
	 * @param mixed        $value     The value to set at $path in $input
	 * @param string       $separator "." Can be set to any string you want to use as separator of path parts.
	 *
	 * @return void
	 */
	public static function setPath(array $input, $path, $value, string $separator = "."): array {
		return static::getInstance(static::$pathClass)->set($input, $path, $value, $separator);
	}
	
	/**
	 * Removes the values at the given $path"s from the $input array.
	 * It can also remove multiple values at once if you use wildcards.
	 *
	 * NOTE: The method tries to remove empty remains recursively when the last
	 * child was removed from the branch. If you don"t want to use this behaviour
	 * set $removeEmptyRemains to false.
	 *
	 * @param array        $input              The array to remove the values from
	 * @param array|string $path               The path which defines which values have to be removed
	 * @param array        $options            Additional config options
	 *                                         - separator (string) ".": Can be set to any string
	 *                                         you want to use as separator of path parts.
	 *                                         - keepEmpty (bool) TRUE: Set this to false to disable
	 *                                         the automatic cleanup of empty remains when the lowest
	 *                                         child was removed from a tree.
	 *
	 * @return array
	 */
	public static function removePath(array $input, $path, array $options = []): array {
		$options = Options::make($options, [
			"separator" => [
				"type"    => "string",
				"default" => ".",
			],
			"keepEmpty" => [
				"type"    => "bool",
				"default" => FALSE,
			],
		]);
		return static::getInstance(static::$pathClass)->remove($input, $path, $options["separator"], $options["keepEmpty"]);
	}
	
	
	/**
	 * This method can be used to apply a filter to all values the given $path matches.
	 * The given $callback will receive the following parameters:
	 * $path: "the.path.trough.your.array" to let you decide how to handle the current value
	 * $value: The reference of the current $input's value. Change this value to change $input correspondingly.
	 * The callback should always return void.
	 *
	 * @param array        $input     The array to filter
	 * @param array|string $path      The path which defines the values to filter
	 * @param callable     $callback  The callback to trigger on every value found by $path
	 * @param string       $separator "." Can be set to any string you want to use as separator of path parts.
	 *
	 * @return array
	 */
	public static function filterPath(array $input, $path, callable $callback, string $separator = "."): array {
		return static::getInstance(static::$pathClass)->filter($input, $path, $callback, $separator);
	}
	
	/**
	 * This is a multi purpose tool to handle different scenarios when dealing with array lists.
	 * It expects a list of similarly structured arrays from which data should be extracted.
	 * But it's probably better to show than to tell, here is what it can do:
	 *
	 * We assume an input array like:
	 * $array = [
	 *        [
	 *            "id" => "234",
	 *            "title" => "medium",
	 *            "asdf" => "asdf",
	 *            "array" => [
	 *                    "id" => "12",
	 *                    "rumpel" => "di",
	 *                    "bar" => "baz",
	 *                ]
	 *        ],
	 *        [
	 *            "id" => "123",
	 *            "title" => "apple",
	 *            "asdf" => "asdf",
	 *            "array" => [
	 *                    "id" => "23",
	 *                    "rumpel" => "pumpel",
	 *                    "foo" => "bar"
	 *                ]
	 *        ]
	 * ];
	 *
	 * // Example 1: Return a list of all "id" values
	 * Arrays::getList($array, "id");
	 * Result: ["234","123"];
	 *
	 * // Example 2: Return a list of all "id" and "title" values
	 * Arrays::getList($array, ["id", "title"]);
	 * Result: [
	 *           ["id" => "234", "title" => "medium"],
	 *           [ "id" => "123", "title" => "apple"]
	 *        ];
	 *
	 * // Example 3: Return a list of all "title" values with their "id" as key
	 * Arrays::getList($array, "title", "id");
	 * Result: ["234" => "medium", "123" => "apple"];
	 *
	 * // Example 4: Path lookup and aliases for longer keys
	 * Arrays::getList($array, ["array.id", "array.rumpel as myAlias"], "id");
	 * Result: [
	 *            "234" => ["array.id" => "12", "myAlias" => "di"],
	 *            "123" => ["array.id" => "23", "myAlias" => "pumpel"]
	 *        ];
	 *
	 * // Example 5: Path lookup and default value for unknown keys
	 * Arrays::getList($array, ["array.id", "array.bar"], "id");
	 * Result: [
	 *            "234" => ["array.id" => "12", "array.bar" => "baz"],
	 *            "123" => ["array.id" => "23", "array.bar" => null] // <-- Null because no value was found!
	 *        ];
	 *
	 * // Example 6: Keep the rows identical but use a column value as key in the result array
	 * Arrays::getList($array, null, "id");
	 * Result: ["234" => [array...], "123" => [array...]];
	 *
	 * // Example 7: Dealing with path based key lookups
	 * Arrays::getList($array, "id", "array.id");
	 * Result: ["12" => "234", "23" => "123"];
	 *
	 * @param array             $input     The input array to gather the list from. Should be a list of arrays.
	 * @param array|string|null $valueKeys The list of value keys to extract from the input list, or a single key as a
	 *                                     string, can contain sub-paths like seen in example 4
	 * @param string            $keyKey    Optional key or sub-path which will be used as key in the result array
	 * @param array             $options   Additional configuration options:
	 *                                     - default (mixed) NULL: The default value if a key was not found in $input.
	 *                                     - separator (string) ".": A separator which is used when splitting string
	 *                                     paths
	 *
	 * @return array|null
	 */
	public static function getList(array $input, $valueKeys, ?string $keyKey = NULL, array $options = []): ?array {
		$options = Options::make($options, [
			"default"   => NULL,
			"separator" => [
				"type"    => "string",
				"default" => ".",
			],
		]);
		
		// Prepare key key
		if (is_null($keyKey)) $keyKey = "";
		
		// Make sure valueKeys is an array
		if (!is_array($valueKeys)) {
			if (is_null($valueKeys)) $valueKeys = [];
			else {
				if (!is_string($valueKeys)) throw new \InvalidArgumentException("The given valueKeys are invalid, only strings and arrays are allowed!");
				$valueKeys = [$valueKeys];
			}
		}
		
		return static::getInstance(static::$pathClass)->getList(
			$input, $valueKeys, $keyKey, $options["default"], $options["separator"]);
	}
	
	/**
	 * Removes the given list of keys / paths from the $input array and returns the results
	 *
	 * @param array $list          The array to strip the unwanted fields from
	 * @param array $pathsToRemove The keys / paths to remove from $input
	 * @param array $options       Additional config options
	 *                             - separator (".") Can be set to any string
	 *                             you want to use as separator of path parts.
	 *                             - removeEmpty (TRUE) Set this to false to disable
	 *                             the automatic cleanup of empty remains when the lowest
	 *                             child was removed from a tree.
	 *
	 * @return array
	 */
	public static function without(array $list, array $pathsToRemove, array $options = []): array {
		foreach ($pathsToRemove as $path)
			$list = static::removePath($list, $path, $options);
		return $list;
	}
	
	/**
	 * Flattens a multidimensional array into a one dimensional array, while keeping
	 * their keys as "path". So for example:
	 *
	 * $array = ["foo" => 123, "bar" => ["baz" => 234]];
	 * $arrayFlattened = ["foo" => 123, "bar.baz" => 234];
	 *
	 * @param iterable $list    The array or iterable to flatten
	 * @param array    $options Additional config options:
	 *                          - separator (string) default ".": Is used to define the separator
	 *                          that glues the "key's" of the path together
	 *                          - arraysOnly (bool) default FALSE: By default this method traverses
	 *                          all kinds of iterable objects as well as arrays. If you only want
	 *                          to traverse arrays set this to TRUE
	 *
	 * @return array
	 */
	public static function flatten(iterable $list, array $options = []): array {
		// Prepare options
		$options = Options::make($options, [
			"separator"  => [
				"default" => ".",
				"type"    => "string",
			],
			"arraysOnly" => [
				"default" => FALSE,
				"type"    => "bool",
			],
		]);
		
		// Run the flattener
		$out = [];
		static::flattenWalker($out, $list, [], $options["separator"], $options["arraysOnly"]);
		return $out;
	}
	
	/**
	 * Internal helper to recursively iterate the given $input array and flatten it's contents into an array
	 *
	 * @param array    $out
	 * @param iterable $input
	 * @param array    $path
	 * @param string   $separator
	 * @param bool     $arraysOnly
	 */
	protected static function flattenWalker(array &$out, iterable $input, array $path, string $separator, bool $arraysOnly): void {
		foreach ($input as $k => $v) {
			$path[] = str_replace($separator, "\\" . $separator, $k);
			if ($arraysOnly && is_array($arraysOnly) || !$arraysOnly && is_iterable($v)) {
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
	 * @param iterable $list    The flattened list to inflate
	 * @param array    $options Additional config options:
	 *                          - separator (string) default ".": Is used to define the separator
	 *                          that glues the "key's" of the path together
	 *
	 * @return array
	 */
	public static function unflatten(iterable $list, array $options = []): array {
		// Prepare options
		$options = Options::make($options, [
			"separator" => [
				"default" => ".",
				"type"    => "string",
			],
		]);
		
		$out = [];
		foreach ($list as $path => $value)
			$out = Arrays::setPath($out, $path, $value, $options["separator"]);
		return $out;
	}
	
	/**
	 * Works exactly like array_map but traverses the array recursively.
	 *
	 * You callback will get the following arguments:
	 * $currentValue, $currentKey, $pathOfKeys, $inputArray
	 *
	 * @param array    $list     The array to iterate
	 * @param callable $callback The callback to execute for every child of the given array.
	 *
	 * @return array
	 */
	public static function mapRecursive(array $list, callable $callback): array {
		return static::mapRecursiveWalker($list, $list, [], $callback);
	}
	
	/**
	 * Internal helper to recursively iterate over a given $list and execute a callback for ever child of it.
	 *
	 * @param array    $list
	 * @param array    $currentList
	 * @param array    $path
	 * @param callable $callback
	 *
	 * @return array
	 */
	protected static function mapRecursiveWalker(array $list, array $currentList, array $path, callable $callback): array {
		$output = [];
		foreach ($currentList as $k => $v) {
			$path[] = $k;
			if (is_array($v)) $v = static::mapRecursiveWalker($list, $v, $path, $callback);
			else $v = call_user_func($callback, $v, $k, $path, $list);
			$output[$k] = $v;
			array_pop($path);
		}
		return $output;
	}
	
	/**
	 * Receives a xml-input and converts it into a multidimensional array
	 *
	 * @param string|array|null|\DOMNode|\SimpleXMLElement $input
	 * @param bool                                         $asAssocArray If this is set to true the result object is
	 *                                                                   converted to a more readable associative
	 *                                                                   array. Be careful with this! There might be
	 *                                                                   sideEffects, like changing paths when the
	 *                                                                   result array has a changing number of nodes.
	 *
	 * @return array
	 */
	public static function makeFromXml($input, bool $asAssocArray = FALSE): array {
		return static::getInstance(static::$generatorClass)->fromXml($input, $asAssocArray);
	}
	
	/**
	 * This is the counterpart of Arrays::makeFromXml() which takes it's output
	 * and converts it back into a stringified XML format.
	 *
	 * @param array $input    The array to convert to a XML
	 * @param bool  $asString TRUE to return a string instead of a simple xml element
	 *
	 * @return \SimpleXMLElement|string
	 * @todo consolidate, write tests and document
	 * @todo NOTE: Still in development
	 */
	public static function dumpToXml(array $input, bool $asString = FALSE) {
		return static::getInstance(static::$dumperClass)->toXml($input, $asString);
	}
	
	/**
	 * The method receives an object of any kind and converts it into a multidimensional array
	 *
	 * @param object $input Any kind of object that should be converted into an array
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public static function makeFromObject($input): array {
		return static::getInstance(static::$generatorClass)->fromObject($input);
	}
	
	/**
	 * Receives a string list like: "1,asdf,foo, bar" which will be converted into [1, "asdf", "foo", "bar"]
	 * NOTE: the result is automatically trimmed and type converted into: numbers, TRUE, FALSE an null.
	 *
	 * @param string $input     The value to convert into an array
	 * @param string $separator The separator to split the string at. By default: ","
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public static function makeFromStringList($input, string $separator = ","): array {
		return static::getInstance(static::$generatorClass)->fromStringList($input, $separator);
	}
	
	/**
	 * Receives a string value and parses it as a csv into an array
	 *
	 * @param string $input         The csv string to parse
	 * @param bool   $firstLineKeys Set to true if the first line of the csv are keys for all other rows
	 * @param string $delimiter     The delimiter between multiple fields
	 * @param string $quote         The enclosure or quoting tag
	 *
	 * @return array[]
	 * @throws ArrayGeneratorException
	 */
	public static function makeFromCsv($input, bool $firstLineKeys = FALSE,
									   string $delimiter = ",", string $quote = "\""): array {
		return static::getInstance(static::$generatorClass)->fromCsv($input, $firstLineKeys, $delimiter, $quote);
	}
	
	/**
	 * Creates an array out of a json data string. Throws an exception if an error occurred!
	 * Only works with json objects or arrays. Other values will throw an exception
	 *
	 * @param string $input
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public static function makeFromJson($input): array {
		return static::getInstance(static::$generatorClass)->fromJson($input);
	}
	
	/**
	 * Internal helper to request the singleton instances of the used helper classes
	 *
	 * @param string $className
	 *
	 * @return \Neunerlei\Arrays\ArrayDumper|\Neunerlei\Arrays\ArrayGenerator|\Neunerlei\Arrays\ArrayPaths
	 * @throws \Neunerlei\Arrays\ArrayException
	 */
	protected static function getInstance(string $className): object {
		if (isset(static::$instances[$className])) return static::$instances[$className];
		if (!class_exists($className)) throw new ArrayException("Failed to instantiate a class with name: $className, but I could not find it!");
		return static::$instances[$className] = new $className();
	}
}