<?php
/*
 * Copyright 2022 Martin Neundorfer (Neunerlei)
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
 * Last modified: 2022.02.04 at 20:24
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Features;

use TypeError;

abstract class Lists extends Generator
{
    /**
     * If this value is used in a getList value key, the second part will be used as "alias"
     */
    public const GET_LIST_ALIAS_SEPARATOR = ' as ';
    
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
     * Arrays::getList($array, ["id"]);
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
     * Arrays::getList($array, ["title"], "id");
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
     * @param   array              $input      The input array to gather the list from. Should be a list of arrays.
     * @param   array|string|null  $valueKeys  The list of value keys to extract from the input list
     *                                         as a string, can contain sub-paths like seen in example 4
     * @param   string|null|bool   $keyKey     Optional key or sub-path which will be used as key in the result array,
     *                                         Can be set to TRUE to keep the original key
     * @param   array              $options    Additional configuration options:
     *                                         - default (mixed) NULL: The default value if a key was not found in
     *                                         $input.
     *                                         - separator (string) ".": A separator which is used when splitting
     *                                         string paths
     *
     * @return array|null
     */
    public static function getList(
        array $input,
        $valueKeys,
        $keyKey = null,
        array $options = []
    ): ?array
    {
        $valueKeys = $valueKeys ?? [];
        $default = $options['default'] ?? null;
        $separator = isset($options['separator']) && is_string($options['separator'])
            ? $options['separator'] : static::DEFAULT_PATH_SEPARATOR;
        
        $result = [];
        
        if (empty($input)) {
            return $result;
        }
        
        if (! is_array($valueKeys)) {
            if (! is_string($valueKeys)) {
                throw new TypeError('The given valueKeys are invalid, only strings and arrays are allowed!');
            }
            $valueKeys = static::makeFromStringList($valueKeys);
        }
        
        if (isset($valueKeys[0]) && $valueKeys[0] === '*') {
            $valueKeys = [];
        }
        
        // Handle Wildcards
        if (empty($valueKeys)) {
            // @todo this is the behaviour that makes sense, however it breaks some tests
            // therefore it could break some usages, so we keep the old behaviour until the next major release
//            if (empty($keyKey)) {
//                // To simulate the same behaviour we normally have
//                // we don't return the input array, but the array with a numeric index
//                return array_values($input);
//            }
            // @todo deprecated - remove in next major release
            if (empty($keyKey)) {
                return $input;
            }
            
            if ($keyKey === true) {
                return $input;
            }
            
            foreach ($input as $row) {
                $result[static::getPath($row, $keyKey, count($result), $separator)] = $row;
            }
            
            return $result;
        }
        
        // Internal helper to generate a definition for a single list key
        $keyDefinitionGenerator = static function (string $key) use ($separator): array {
            $alias = $key;
            
            // Extract an alias value
            if (strpos($key, static::GET_LIST_ALIAS_SEPARATOR) !== false) {
                $vParts = explode(static::GET_LIST_ALIAS_SEPARATOR, $key);
                $alias = array_pop($vParts);
                $key = implode(static::GET_LIST_ALIAS_SEPARATOR, $vParts);
            }
            
            return [
                'alias' => $alias,
                'key' => $key,
                'isPath' => strpos($key, $separator) !== false && count(static::parsePath($key)) > 1,
            ];
        };
        
        // Build the mapping definition
        $map = array_map($keyDefinitionGenerator, $valueKeys);
        $isSingleKeyPerRow = count($map) === 1;
        
        // Check if we need to inject the key key manually
        if ($keyKey !== true) {
            if (! empty($keyKey)
                && ! in_array($keyKey,
                    array_reduce(
                        $map,
                        static function ($i, $v) {
                            return array_merge($i, [$v['key']]);
                        }, []
                    ), true)
            ) {
                $keyKeyWasInjected = true;
                array_unshift($map, $keyDefinitionGenerator($keyKey));
            }
        }
        
        // Remove linked lists
        unset($keyDefinitionGenerator);
        
        // Build the result set from the input row
        foreach ($input as $initialRowKey => $row) {
            // Ignore if row is no array
            if (! is_array($row)) {
                continue;
            }
            
            // Prepare the row
            $rowResult = [];
            $rowKey = null;
            
            foreach ($map as $def) {
                $key = $def['key'];
                
                if ($def['isPath']) {
                    $value = static::getPath($row, $key, $default, $separator);
                } elseif (array_key_exists($key, $row)) {
                    $value = $row[$key];
                } else {
                    $value = $default;
                }
                
                if ($def['alias'] === $keyKey) {
                    $rowKey = $value;
                    
                    if (isset($keyKeyWasInjected)) {
                        continue;
                    }
                }
                
                // Break if we just have a single result in a row
                // The injected key key always comes first, so it could never be ignored
                // If an alias was given for the field -> we force the output of an array,
                // otherwise the alias would be rather pointless
                if ($isSingleKeyPerRow && $key === $def['alias']) {
                    $rowResult = $value;
                    break;
                }
                
                $rowResult[$def['alias']] = $value;
            }
            
            // Either auto-extend the numeric index or inject the row key we resolved
            if ($keyKey === true) {
                $result[$initialRowKey] = $rowResult;
            } elseif ($rowKey === null) {
                $result[] = $rowResult;
            } else {
                $result[$rowKey] = $rowResult;
            }
            
            unset($rowResult, $def);
        }
        unset($map);
        
        return $result;
    }
}
