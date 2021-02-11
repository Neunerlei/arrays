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
 * Last modified: 2021.02.11 at 19:07
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Traits;


use InvalidArgumentException;
use TypeError;

trait PathParsingTrait
{

    /**
     * A list of string path"s and their parsed equivalent for faster lookups
     *
     * @var array
     */
    protected static $pathCache = [];

    // @todo implement this
    protected static $pathCacheLimiter = [];

    /**
     * This method is used to convert a string into a path array.
     * It will also validate already existing path arrays.
     *
     * By default a period (.) is used to separate path parts like: "my.array.path" => ["my","array","path"].
     * If you require another separator you can set another one by using the $separator parameter.
     * In most circumstances it will make more sense just to escape a separator, tho. Do that by using a backslash like:
     * "my\.array.path" => ["my.array", "path"].
     *
     * @param   array|string  $path       The path to parse as described above.
     * @param   string|null   $separator  "." Can be set to any string you want to use as separator of path parts.
     *
     * @return array
     */
    public static function parsePath($path, ?string $separator = null): array
    {
        if (empty($path)) {
            throw new InvalidArgumentException('The given path is empty!');
        }

        if (! is_string($path) && ! is_numeric($path) && ! is_array($path)) {
            throw new TypeError(
                'The given path: ' . json_encode($path, JSON_THROW_ON_ERROR)
                . ' is not valid! Only strings, numbers and arrays are supported!'
            );
        }

        $separator = $separator ?? static::DEFAULT_PATH_SEPARATOR;

        // Check if the given path array is valid
        if (is_array($path)) {
            $parts = array_values($path);
            array_walk_recursive($path, static function ($v) use ($parts) {
                if (! is_string($v) && ! is_numeric($v) && ! is_array($v)) {
                    throw new TypeError(
                        'The given path array: ' . json_encode($parts, JSON_THROW_ON_ERROR)
                        . ' should only contain numbers, strings and arrays!');
                }
            });

            return $parts;
        }

        $path     = (string)$path;
        $cacheKey = md5($path . $separator);

        if (isset(static::$pathCache[$cacheKey])) {
            // Push the cache key to the end of the list -> meaning it is less likely to be
            // dropped when the list grows
            $i = array_search($cacheKey, static::$pathCacheLimiter, true);
            array_splice(static::$pathCacheLimiter, $i, 1);
            static::$pathCacheLimiter[] = $cacheKey;

            return static::$pathCache[$cacheKey];
        }

        // Check if we can use the fast lane
        if (static::canUseFastLane($path, $separator)) {
            $parts = [$path];

        } else {
            // Build escaping list
            $escapableChars   = static::ESCAPABLE_CHARS;
            $escapableChars[] = $separator;
            $escaping         = [];
            foreach ($escapableChars as $c => $k) {
                $escaping['in'][]    = '\\' . $k;
                $escaping['token'][] = '@ESCAPED@' . $c . '@@';
                $escaping['out'][]   = $k;
            }

            // Escape the incoming string
            $lengthBeforeEscaping = strlen($path);
            $path                 = str_replace($escaping['in'], $escaping['token'], $path);
            $isEscaped            = $lengthBeforeEscaping !== strlen($path);

            // Resolve braces
            $braces = [$path];
            if (strpos($path, '[') !== false) {
                $braces = static::resolveBracesInPath($path, $separator);
            }

            // Parse the path from a string
            $parts = array_values(array_filter(
                array_map('trim', explode($separator, $braces[0])), static function ($v) {
                return $v !== '' && $v !== null;
            }));

            // Restore escaped chars
            if ($isEscaped) {
                foreach ([&$parts, &$braces] as &$list) {
                    foreach ($list as &$item) {
                        $item = str_replace($escaping['token'], $escaping['out'], $item);
                    }
                    unset($item);
                }
                unset($list);
            }

            // Inject braces into the path
            if (count($braces) > 1) {
                $partsString = json_encode($parts, JSON_THROW_ON_ERROR);
                foreach ($braces as $braceId => $brace) {
                    $partsString = str_replace('"\\\\(\\\\b' . $braceId . '\\\\)"', $brace, $partsString);
                }
                $parts = json_decode($partsString, true, 512, JSON_THROW_ON_ERROR);
            }

        }

        if (empty($parts)) {
            throw new InvalidArgumentException('The path parsing resulted in an empty array!');
        }

        static::$pathCache[$cacheKey] = $parts;

        // Make sure we don't create a memory leak and only keep the last 20 paths in storage
        static::$pathCacheLimiter[] = $cacheKey;
        if (count(static::$pathCacheLimiter) > 20) {
            unset(static::$pathCache[array_shift(static::$pathCacheLimiter)]);
        }

        return $parts;
    }

    /**
     * Internal helper which is used to recursively parse the path braces into an array definition.
     *
     * @param   string  $path       The path to extract the braces from
     * @param   string  $separator  The separator that is used to split path parts
     *
     * @return array
     * @throws \JsonException
     */
    protected static function resolveBracesInPath(string $path, string $separator): array
    {
        $pathString = $path;

        // Validate the path
        if (substr_count($path, '[') !== substr_count($path, ']')) {
            throw new InvalidArgumentException(
                'The given path "' . $path .
                '" is invalid! There is a mismatch between opening and closing braces!');
        }

        // Prepare the working variables
        $bracesPath   = [];
        $braceCounter = 0;
        $braceId      = 0;
        $braces       = [$braceId => ''];

        // Read the braces char by char
        $length = strlen($pathString);
        for ($i = 0; $i < $length; $i++) {
            $char = $pathString[$i];
            if ($char === '[') {
                // Open new brace
                $bracesPath[]     = $braceId;
                $braces[$braceId] .= '\\(\\b' . (++$braceCounter) . '\\)';
                $braceId          = $braceCounter;
                if (! isset($braces[$braceId])) {
                    $braces[$braceId] = '';
                }
            }
            $braces[$braceId] .= $char;
            if ($char === ']') {
                $braceId = (int)array_pop($bracesPath);
            }
        }

        // Split the stored braces into parts
        foreach ($braces as $braceId => $brace) {
            if ($braceId === 0) {
                continue;
            }

            // Split at the comma
            $brace = trim($brace, '[],');
            preg_match_all('/(\\.|[^,])+/', $brace, $braceParts);
            $braces[$braceId] = json_encode(array_map(function ($part) use ($separator) {
                $part = trim($part);

                if (stripos($part, $separator)) {
                    return static::parsePath($part, $separator);
                }

                return $part;
            }, $braceParts[0]), JSON_THROW_ON_ERROR);
        }

        return $braces;
    }
}
