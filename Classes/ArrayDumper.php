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


use DOMDocument;
use SimpleXMLElement;

/**
 * Class ArrayDumper
 *
 * @package Neunerlei\Arrays
 * @todo    this could be moved into a trait and even broken up further for better readability it would also remove a
 *          lot of overhead
 */
class ArrayDumper
{
    /**
     * Dumps the given array as JSON string.
     * Simply a wrapper around json_encode to throw errors if the encoding fails
     *
     * @param   array  $array    The array to convert to json
     * @param   int    $options  Bitmask consisting of one or multiple of the JSON_ constants.
     *                           The behaviour of these constants is described on the JSON constants page.
     * @param   int    $depth    User specified recursion depth.
     *
     * @return string
     * @throws \JsonException
     * @see \json_encode() for possible options
     * @see https://php.net/manual/en/function.json-encode.php
     */
    public function toJson(array $array, int $options = 0, int $depth = 512): string
    {
        return json_encode($array, $options | JSON_THROW_ON_ERROR, $depth);
    }

    /**
     * Dumps the given array as pretty printed JSON string.
     *
     * @param   array  $array    The array to convert to json
     * @param   int    $options  Bitmask consisting of one or multiple of the JSON_ constants.
     *                           The behaviour of these constants is described on the JSON constants page.
     * @param   int    $depth    User specified recursion depth.
     *
     * @return string
     * @see \Neunerlei\Arrays\ArrayDumper::toJson()
     */
    public function toJsonPretty(array $array, int $options = 0, int $depth = 512): string
    {
        return $this->toJson($array, $options | JSON_PRETTY_PRINT, $depth);
    }

    /**
     * Receives the result of Arrays::makeFromXml() and converts the array back into an xml format
     *
     * @param   array  $array
     * @param   bool   $asString
     *
     * @return \SimpleXMLElement|string
     * @throws \Neunerlei\Arrays\ArrayDumperException
     * @codeCoverageIgnore
     */
    public function toXml(array $array, bool $asString = false)
    {
        // Die if we got an invalid node
        if (count($array) !== 1) {
            throw new ArrayDumperException('Only arrays with a single root node can be converted into xml');
        }

        // Helper to traverse the given array recursively
        $walker = static function (array $entry, array $path, ?SimpleXMLElement $xml, callable $walker) {
            if (! is_array($entry)) {
                throw new ArrayDumperException('All entries have to be arrays, but ' . implode('.', $path) . " isn't");
            }

            if (! isset($entry['tag'])) {
                throw new ArrayDumperException('All entries in an XML array have to specify a "tag" property, but ' .
                                               implode('.', $path) . " doesn't have one");
            }

            if ($xml === null) {
                $xml   = new SimpleXMLElement(
                    '<?xml version="1.0" encoding="utf-8" standalone="yes"?><' . $entry['tag'] . '/>');
                $child = $xml;
            } elseif (! empty($entry['content'])) {
                $content = $entry['content'];
                if (stripos($entry['content'], '<![CDATA') !== false) {
                    $child = $xml->addChild($entry['tag']);
                    $node  = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection(
                        preg_replace('~<!\[CDATA\[(.*?)]]>~', '$1', $content)
                    ));
                } else {
                    $child = $xml->addChild($entry['tag'], htmlspecialchars($content));
                }
            } else {
                $child = $xml->addChild($entry['tag']);
            }

            foreach ($entry as $prop => $value) {
                if ($prop === 'tag' || $prop === 'content') {
                    continue;
                }
                if (! is_string($prop)) {
                    $pathLocal   = $path;
                    $pathLocal[] = $entry['tag'];
                    if (! isset($value['tag'])) {
                        $pathLocal[] = $prop;
                    }
                    $walker($value, $pathLocal, $child, $walker);
                } elseif (strncmp($prop, '@', 1) === 0) {
                    $child->addAttribute(substr($prop, 1), $value);
                } else {
                    throw new ArrayDumperException('Invalid entry prop: ' . $prop . ' at ' . implode('.', $path));
                }
            }

            return $xml;
        };

        // Start the recursive array traversing
        $xml = $walker($array[0], [], null, $walker);

        // Return the xml if we don't want a string
        if (! $asString) {
            return $xml;
        }

        // Format the output
        $xmlDocument                     = new DOMDocument('1.0', 'utf-8');
        $xmlDocument->formatOutput       = true;
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->loadXML($xml === null ? '' : $xml->asXML());

        return $xmlDocument->saveXML();
    }
}
