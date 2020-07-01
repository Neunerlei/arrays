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

namespace Neunerlei\Arrays;

use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ArrayPaths
 *
 * This is functionality which was extracted from Arrays in order to keep the class somewhat short...
 *
 * @package Neunerlei\Arrays
 * @internal
 */
class ArrayPaths
{
    
    /**
     * The list of additional characters that can be escaped when a path is parsed
     */
    protected const ESCAPABLE_CHARS
        = [
            "*",
            "[",
            "]",
            ",",
        ];
    
    /**
     * The different key types
     */
    protected const KEY_TYPE_DEFAULT  = 0;
    protected const KEY_TYPE_WILDCARD = 1;
    protected const KEY_TYPE_KEYS     = 2;
    
    /**
     * A list of string path"s and their parsed equivalent for faster lookups
     *
     * @var array
     */
    protected $pathCache = [];
    
    /**
     * @param           $path
     * @param   string  $separator
     *
     * @return array
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::parsePath()
     */
    public function parsePath($path, string $separator = "."): array
    {
        if (empty($path)) {
            return [];
        }
        if (! is_string($path) && ! is_numeric($path) && ! is_array($path)) {
            throw new InvalidArgumentException("The given path: " . json_encode($path, JSON_THROW_ON_ERROR)
                                               . " is not valid! Only strings, numbers and arrays are supported!");
        }
        
        // Check if the given path array is valid
        if (is_array($path)) {
            $parts = array_values($path);
            array_walk_recursive($path, static function ($v) use ($parts) {
                if (! is_string($v) && ! is_numeric($v) && ! is_array($v)) {
                    throw new InvalidArgumentException(
                        "The given path array: " . json_encode($parts, JSON_THROW_ON_ERROR)
                        . " should only contain numbers, strings and arrays!");
                }
            });
        } else {
            $path     = (string)$path;
            $cacheKey = $path . $separator;
            if (! empty($this->pathCache[$cacheKey])) {
                return $this->pathCache[$cacheKey];
            }
            
            // Check if we can use the fast lane
            if (! $this->canUseFastLane($path, $separator)) {
                // Build escaping list
                $escapableChars   = static::ESCAPABLE_CHARS;
                $escapableChars[] = $separator;
                $escaping         = [];
                foreach ($escapableChars as $c => $k) {
                    $escaping["in"][]    = "\\" . $k;
                    $escaping["token"][] = "@ESCAPED@$c@@";
                    $escaping["out"][]   = $k;
                }
                
                // Escape the incoming string
                $lengthBeforeEscaping = strlen($path);
                $path                 = str_replace($escaping["in"], $escaping["token"], $path);
                $isEscaped            = $lengthBeforeEscaping !== strlen($path);
                
                // Resolve braces
                $braces = [$path];
                if (strpos($path, "[") !== false) {
                    $braces = $this->resolveBracesInPath($path, $separator);
                }
                
                // Parse the path from a string
                $parts = array_values(array_filter(
                    array_map("trim", explode($separator, $braces[0])), static function ($v) {
                    return $v !== "" && $v !== null;
                }));
                
                // Restore escaped chars
                if ($isEscaped) {
                    foreach ([&$parts, &$braces] as &$list) {
                        foreach ($list as &$item) {
                            $item = str_replace($escaping["token"], $escaping["out"], $item);
                        }
                        unset($item);
                    }
                    unset($list);
                }
                
                // Inject braces into the path
                if (count($braces) > 1) {
                    $partsString = json_encode($parts, JSON_THROW_ON_ERROR);
                    foreach ($braces as $braceId => $brace) {
                        $partsString = str_replace("\"\\\\(\\\\b$braceId\\\\)\"", $brace, $partsString);
                    }
                    $parts = json_decode($partsString, true, 512, JSON_THROW_ON_ERROR);
                }
                
            } else {
                $parts = [$path];
            }
            
            $this->pathCache[$cacheKey] = $parts;
        }
        
        return $parts;
    }
    
    /**
     * @param           $pathA
     * @param           $pathB
     * @param   string  $separatorA
     * @param   string  $separatorB
     *
     * @return array
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::mergePaths()
     */
    public function mergePaths($pathA, $pathB, ?string $separatorA = ".", ?string $separatorB = "."): array
    {
        $pathA = $this->parsePath($pathA, $separatorA);
        $pathB = $this->parsePath($pathB, $separatorB);
        foreach ($pathB as $p) {
            $pathA[] = $p;
        }
        
        return $pathA;
    }
    
    /**
     * @param   array|mixed   $list       The array to check
     * @param   array|string  $path       The path to check for in $input
     * @param   string        $separator  Default: "." Can be set to any string you want to use as separator of path
     *                                    parts.
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::hasPath()
     */
    public function has(array $list, $path, string $separator = "."): bool
    {
        // Fastlane for simple paths
        if ($this->canUseFastLane($path, $separator)) {
            return array_key_exists($path, $list);
        }
        
        // Walk the distance
        $path = $this->parsePath($path, $separator);
        if (empty($path)) {
            throw new InvalidArgumentException("The given path was empty!");
        }
        try {
            $this->hasWalker($list, $path);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Internal walker method for "has"
     *
     * @param   array  $list
     * @param   array  $path
     *
     * @throws \RuntimeException
     * @see has()
     */
    protected function hasWalker(array $list, array $path): void
    {
        [$keys, $isLastKey] = $this->initWalkerStep($list, $path);
        if (empty($list) || empty($keys)) {
            throw new RuntimeException('hasWalker failed');
        }
        foreach ($keys as $key) {
            // Handle nested paths
            if (is_array($key)) {
                $this->hasWalker($list, $key);
                continue;
            }
            if (! array_key_exists($key, $list)) {
                throw new RuntimeException('hasWalker failed');
            }
            if (! $isLastKey) {
                if (! is_array($list[$key])) {
                    throw new RuntimeException('hasWalker failed');
                }
                $this->hasWalker($list[$key], $path);
            }
        }
    }
    
    /**
     * @param   array         $list       The array to retrieve the path's values from
     * @param   array|string  $path       The path to receive from the $list array
     * @param   NULL | mixed  $default    The value which will be returned if the $path did not match anything .
     * @param   string        $separator  Default: "." Can be set to any string you want to use as separator of path
     *                                    parts .
     *
     * @return array|mixed|NULL
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::get()
     */
    public function get(array $list, $path, $default = null, string $separator = ".")
    {
        // Fastlane for simple paths
        if (empty($list)) {
            return $default;
        }
        if ($this->canUseFastLane($path, $separator)) {
            return array_key_exists($path, $list) ? $list[$path] : $default;
        }
        
        // Walk the distance
        $path = $this->parsePath($path, $separator);
        if (empty($path)) {
            throw new InvalidArgumentException("The given path was empty!");
        }
        
        return $this->getWalker($list, $path, $default);
    }
    
    /**
     * Internal walker method for "get"
     *
     * @param   array  $list
     * @param   array  $path
     * @param          $default
     * @param   bool   $isNested
     *
     * @return array|mixed
     */
    protected function getWalker(array $list, array $path, $default, bool $isNested = false)
    {
        [$keys, $isLastKey, $keyType] = $this->initWalkerStep($list, $path);
        if (empty($list)) {
            return $default;
        }
        $result = [];
        foreach ($keys as $key) {
            // Handle nested paths
            if (is_array($key)) {
                $result = Arrays::merge($result, $this->getWalker($list, $key, $default, true));
                continue;
            }
            
            // Validate if the requested value exists
            if (! array_key_exists($key, $list)) {
                $result[$key] = $default;
                continue;
            }
            
            // Follow the path deeper
            if (! $isLastKey) {
                if (! is_array($list[$key])) {
                    $result[$key] = $default;
                    continue;
                }
                $result[$key] = $this->getWalker($list[$key], $path, $default, $isNested);
            } else {
                $result[$key] = $list[$key];
            }
        }
        
        // Skip post processing if nested
        if ($isNested === true) {
            return $result;
        }
        
        // Flatten result
        if ($keyType === static::KEY_TYPE_DEFAULT) {
            return $result[key($result)];
        }
        
        if ($keyType === static::KEY_TYPE_WILDCARD && reset($keys) === 0) {
            return array_values($result);
        }
        
        return $result;
    }
    
    /**
     * @param   array         $list       The array to set the values in
     * @param   array|string  $path       The path to set $value at
     * @param   mixed         $value      The value to set at $path in $input
     * @param   string        $separator  Default: "." Can be set to any string you want to use as separator of path
     *                                    parts.
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::setPath()
     */
    public function set(array $list, $path, $value, string $separator = "."): array
    {
        // Fastlane for simple paths
        if ($this->canUseFastLane($path, $separator)) {
            $list[$path] = $value;
            
            return $list;
        }
        
        // Walk the distance
        $path = $this->parsePath($path, $separator);
        if (empty($path)) {
            throw new InvalidArgumentException("The given path was empty!");
        }
        $this->setWalker($list, $path, $value);
        
        return $list;
    }
    
    /**
     * Internal walker method for "set"
     *
     * @param   array  $list
     * @param   array  $path
     * @param          $value
     *
     * @see set()
     */
    protected function setWalker(array &$list, array $path, $value): void
    {
        [$keys, $isLastKey] = $this->initWalkerStep($list, $path);
        foreach ($keys as $key) {
            // Handle sub keys
            if (is_array($key)) {
                $this->setWalker($list, $key, $value);
                continue;
            }
            
            // Set the value
            if ($isLastKey) {
                $list[$key] = $value;
                continue;
            }
            
            // Go deeper
            if (! array_key_exists($key, $list) || ! is_array($list[$key])) {
                $list[$key] = [];
            }
            $this->setWalker($list[$key], $path, $value);
        }
    }
    
    /**
     * @param   array         $list            The array to remove the values from
     * @param   array|string  $path            The path which defines which values have to be removed
     * @param   string        $separator       Default: "." Can be set to any string you want to use as separator of
     *                                         path parts.
     * @param   bool          $keepEmpty       Set this to false to disable the automatic cleanup of empty remains when
     *                                         the lowest child was removed from a tree.
     *
     * @return array
     * @see \Neunerlei\Arrays\Arrays::removePath()
     */
    public function remove(array $list, $path, string $separator = ".", bool $keepEmpty = false): array
    {
        // Try to use fast lane
        if ($this->canUseFastLane($list, $separator)) {
            unset($list[$path]);
            
            return $list;
        }
        
        // Walk the distance
        $path = $this->parsePath($path, $separator);
        if (empty($path)) {
            throw new InvalidArgumentException("The given path was empty!");
        }
        $this->removeWalker($list, $path, $keepEmpty);
        
        return $list;
    }
    
    /**
     * Internal walker method for "remove"
     *
     * @param   array  $list
     * @param   array  $path
     * @param   bool   $keepEmpty
     *
     * @see remove()
     */
    protected function removeWalker(array &$list, array $path, bool $keepEmpty): void
    {
        [$keys, $isLastKey] = $this->initWalkerStep($list, $path);
        foreach ($keys as $key) {
            if (is_array($key)) {
                $this->removeWalker($list, $key, $keepEmpty);
                continue;
            }
            
            if ($isLastKey) {
                unset($list[$key]);
                continue;
            }
            if (is_array($list[$key])) {
                $this->removeWalker($list[$key], $path, $keepEmpty);
            }
            if (! $keepEmpty && empty($list[$key])) {
                unset($list[$key]);
            }
        }
    }
    
    /**
     * @param   array         $list       The array to filter
     * @param   array|string  $path       The path which defines the values to filter
     * @param   callable      $callback   The callback to trigger on every value found by $path
     * @param   string        $separator  Default: "." Can be set to any string you want to use as separator of path
     *                                    parts.
     *
     * @return array
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::filterPath()
     */
    public function filter(array $list, $path, callable $callback, string $separator = "."): array
    {
        $path = $this->parsePath($path, $separator);
        if (empty($path)) {
            throw new InvalidArgumentException("The given path was empty!");
        }
        $this->filterWalker($list, $path, [], $callback, $separator, $list);
        
        return $list;
    }
    
    /**
     * Internal walker method for "filter"
     *
     * @param   array     $list
     * @param   array     $path
     * @param   array     $localPath
     * @param   callable  $callback
     * @param   string    $separator
     * @param   array     $inputArray
     *
     * @see filter()
     */
    protected function filterWalker(
        array &$list,
        array $path,
        array $localPath,
        callable $callback,
        string $separator,
        array $inputArray
    ): void {
        [$keys, $isLastKey] = $this->initWalkerStep($list, $path);
        foreach ($keys as $key) {
            $localPath[] = $key;
            if (is_array($key)) {
                $this->filterWalker($list, $key, $localPath, $callback, $separator, $inputArray);
            } elseif ($isLastKey) {
                $list[$key] = $callback($list[$key], $key, $localPath, $inputArray);
            } elseif (is_array($list[$key])) {
                $this->filterWalker($list[$key], $path, $localPath, $callback, $separator, $inputArray);
            }
            array_pop($localPath);
        }
    }
    
    /**
     * @param   array       $input      The input array to gather the list from. Should be a list of arrays.
     * @param   array       $valueKeys  The list of value keys to extract from the list, can contain sub-paths
     *                                  like seen in example 4
     * @param   string      $keyKey     Optional key or sub-path which will be used as key in the result array
     * @param   null|mixed  $default    The default value if a key was not found in $input.
     * @param   string      $separator  A separator which is used when splitting string paths
     *
     * @return array|null
     * @throws \InvalidArgumentException
     * @see \Neunerlei\Arrays\Arrays::getList()
     */
    public function getList(
        array $input,
        array $valueKeys,
        string $keyKey = "",
        $default = null,
        string $separator = "."
    ): ?array {
        if (empty($input)) {
            return $default;
        }
        if (isset($valueKeys[0]) && $valueKeys[0] === "*") {
            $valueKeys = [];
        }
        
        // Prepare working variables
        $result           = [];
        $hasKeyKey        = ! empty($keyKey);
        $isSingleValueKey = count($valueKeys) === 1;
        
        // Handle Wildcards
        if (empty($valueKeys)) {
            if (! $hasKeyKey) {
                return $input;
            }
            foreach ($input as $row) {
                $result[$this->get($row, $keyKey, null, $separator)] = $row;
            }
            
            return $result;
        }
        
        // Add key key to the list of required keys
        $keyKeyWasInjected = false;
        if ($hasKeyKey && ! in_array($keyKey, $valueKeys, true)) {
            $valueKeys[]       = $keyKey;
            $keyKeyWasInjected = true;
        }
        
        
        // This block checks if we have to resolve keys which are sub-paths in the current array list.
        // It is possible to define a valueKey like sub.array.id to extract that deeper level's
        // information and put it into the current context. The key will be the same as the path,
        // in our case: "sub.array.id", if we want something more speaking we can
        // define an alias like sub.array.id as myId. Now the value will show up with myId as key.
        // This block prepares the parsing, so we don't have to do it in every loop
        $pathValueKeys = $simpleValueKeys = $keyAliasMap = [];
        array_map(static function ($v) use (
            $separator,
            &$pathValueKeys,
            &$simpleValueKeys,
            &$keyAliasMap,
            $isSingleValueKey
        ) {
            // Store the alias
            $vOrg           = $alias = $v;
            $aliasSeparator = " as ";
            if (stripos($v, $separator) !== false) {
                // Check for an alias || Ignore when only one value will be returned -> save performance (a bit at least)
                if (! $isSingleValueKey && stripos($v, $aliasSeparator) !== false) {
                    $v     = explode($aliasSeparator, $v);
                    $alias = array_pop($v);
                    $v     = implode($aliasSeparator, $v);
                }
                $pathValueKeys[$alias] = $v;
                $simpleValueKeys[]     = $alias;
            } else {
                $simpleValueKeys[] = $v;
            }
            $keyAliasMap[$vOrg] = $alias;
        }, $valueKeys);
        $simpleValueKeys = array_fill_keys($simpleValueKeys, $default);
        
        // Loop over the list of rows
        $emptyMarker = "__EMPTY__93223asd912__";
        foreach ($input as $row) {
            // Only simple value keys -> use the fast lane
            $rowValues = array_intersect_key($row, $simpleValueKeys);
            
            // Contains path value keys -> also gather their values
            foreach ($pathValueKeys as $alias => $pathValueKey) {
                // Read the path value from the current context
                $value = $this->get($row, $pathValueKey, $emptyMarker, $separator);
                if ($value !== $emptyMarker) {
                    $rowValues[$alias] = $value;
                }
            }
            
            // Check if we are completely empty
            if (empty($rowValues)) {
                continue;
            }
            
            // Get key key
            $keyKeyValue = $hasKeyKey ? $rowValues[$keyAliasMap[$keyKey]] : null;
            
            // Check if we have a single value key -> strip the surrounding array
            if ($isSingleValueKey) {
                // Remove if the key key was injected and not part of the requested columns
                if ($keyKeyWasInjected) {
                    unset($rowValues[$keyAliasMap[$keyKey]]);
                }
                
                // Extract first value
                $rowValues = reset($rowValues);
            } else {
                // Fill up with default values (if we are missing some)
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $rowValues = array_merge($simpleValueKeys, $rowValues);
                
                // Remove if the key key was injected and not part of the requested columns
                if ($keyKeyWasInjected) {
                    unset($rowValues[$keyAliasMap[$keyKey]]);
                }
            }
            
            // Append to result
            if ($hasKeyKey && ! empty($keyKeyValue)) {
                $result[$keyKeyValue] = $rowValues;
            } else {
                $result[] = $rowValues;
            }
        }
        
        // Done
        return $result;
    }
    
    /**
     * Internal helper which is used to recursively parse the path braces into an array definition.
     *
     * @param   string  $path       The path to extract the braces from
     * @param   string  $separator  The separator that is used to split path parts
     *
     * @return array
     */
    protected function resolveBracesInPath(string $path, string $separator): array
    {
        $pathString = $path;
        
        // Validate the path
        if (substr_count($path, "[") !== substr_count($path, "]")) {
            throw new InvalidArgumentException("The given path \"$path\" is invalid! There is a mismatch between opening and closing braces!");
        }
        
        // Prepare the working variables
        $bracesPath   = [];
        $braceCounter = 0;
        $braceId      = 0;
        $braces       = [$braceId => ""];
        
        // Read the braces char by char
        $length = strlen($pathString);
        for ($i = 0; $i < $length; $i++) {
            $char = $pathString[$i];
            if ($char === "[") {
                // Open new brace
                $bracesPath[]     = $braceId;
                $braces[$braceId] .= "\\(\\b" . (++$braceCounter) . "\\)";
                $braceId          = $braceCounter;
                if (! isset($braces[$braceId])) {
                    $braces[$braceId] = "";
                }
            }
            $braces[$braceId] .= $char;
            if ($char === "]") {
                $braceId = (int)array_pop($bracesPath);
            }
        }
        
        // Split the stored braces into parts
        foreach ($braces as $braceId => $brace) {
            if ($braceId === 0) {
                continue;
            }
            
            // Split at the comma
            $brace = trim($brace, "[],");
            preg_match_all("/(\\.|[^,])+/", $brace, $braceParts);
            $braces[$braceId] = json_encode(array_filter(array_map(function ($part) use ($separator) {
                $part = trim($part);
                if (empty($part)) {
                    return null;
                }
                if (stripos($part, $separator)) {
                    return $this->parsePath($part, $separator);
                }
                
                return $part;
            }, $braceParts[0])), JSON_THROW_ON_ERROR);
        }
        
        return $braces;
    }
    
    /**
     * Internal helper which decides if a path can use the "fast-lane" resolution or not
     *
     * @param   array|string  $path       The path to check
     * @param   string        $separator  The path separator character
     *
     * @return bool
     */
    protected function canUseFastLane($path, string $separator): bool
    {
        if (! is_string($path)) {
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
    protected function initWalkerStep(array $input, array &$path): array
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
            if ($key === "*") {
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
