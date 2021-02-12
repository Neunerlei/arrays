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
 * Last modified: 2021.02.11 at 19:25
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Features;


use DOMDocument;
use Neunerlei\Arrays\ArrayDumperException;
use SimpleXMLElement;

abstract class Dumper extends Lists
{
    /**
     * Dumps the given array as JSON string.
     *
     * @param   array  $input    The array to convert to json
     * @param   array  $options  Additional configuration options for the encoding
     *                           - pretty bool (FALSE): If set to TRUE the JSON will be generated pretty printed
     *                           - options int (0): Bitmask consisting of one or multiple of the JSON_ constants.
     *                           The behaviour of these constants is described on the JSON constants page.
     *                           JSON_THROW_ON_ERROR is set by default for all operations
     *                           - depth int (512): User specified recursion depth.
     *
     * @return string
     * @throws \JsonException if the encoding fails
     * @see          \json_encode() for possible options
     * @see          https://php.net/manual/en/function.json-encode.php
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public static function dumpToJson(array $input, array $options = []): string
    {
        $jsonOptions = $options['options'] ?? 0;
        $jsonOptions |= JSON_THROW_ON_ERROR;
        if ($options['pretty'] ?? false || in_array('pretty', $options, true)) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        /** @noinspection JsonEncodingApiUsageInspection */
        return json_encode($input, $jsonOptions, $options['depth'] ?? 512);
    }

    /**
     * This is the counterpart of Arrays::makeFromXml() which takes it's output
     * and converts it back into a stringified XML format.
     *
     * @param   array  $input     The array to convert to a XML
     * @param   bool   $asString  TRUE to return a string instead of a simple xml element
     *
     * @return \SimpleXMLElement|string
     * @throws \Neunerlei\Arrays\ArrayDumperException
     * @codeCoverageIgnore
     * @todo write tests and document
     */
    public static function dumpToXml(array $input, bool $asString = false)
    {
        // Die if we got an invalid node
        if (count($input) !== 1) {
            throw new ArrayDumperException('Only arrays with a single root node can be converted into xml');
        }

        // Start the recursive array traversing
        $xml = static::dumpToXmlWalker($input[0], [], null);

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

    /**
     * Internal walker to convert a given $entry array into an xml element
     *
     * @param   array                   $entry
     * @param   array                   $path
     * @param   \SimpleXMLElement|null  $xml
     *
     * @return \SimpleXMLElement
     * @throws \Neunerlei\Arrays\ArrayDumperException
     * @codeCoverageIgnore
     */
    protected static function dumpToXmlWalker(array $entry, array $path, ?SimpleXMLElement $xml): SimpleXMLElement
    {
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
                static::dumpToXmlWalker($value, $pathLocal, $child);
            } elseif (strncmp($prop, '@', 1) === 0) {
                $child->addAttribute(substr($prop, 1), $value);
            } else {
                throw new ArrayDumperException('Invalid entry prop: ' . $prop . ' at ' . implode('.', $path));
            }
        }

        return $xml;
    }
}
