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


use Neunerlei\Arrays\Features\Generator\FromCsvGenerator;
use Neunerlei\Arrays\Features\Generator\FromJsonGenerator;
use Neunerlei\Arrays\Features\Generator\FromObjectGenerator;
use Neunerlei\Arrays\Features\Generator\FromStringListGenerator;
use Neunerlei\Arrays\Features\Generator\FromXmlGenerator;
use SimpleXMLElement;

abstract class Generator extends Path
{
    
    /**
     * The method receives an object of any kind and converts it into a multidimensional array
     *
     * @param   object  $input  Any kind of object that should be converted into an array
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayGeneratorException
     */
    public static function makeFromObject($input): array
    {
        return (new FromObjectGenerator())->generate(...func_get_args());
    }
    
    /**
     * Receives a string list like: "1,foo, bar,-12.3" which will be converted into [1, "foo", "bar", -12.3]
     * NOTE: the result is automatically trimmed and type converted into: numbers, TRUE, FALSE and null.
     *
     * @param   string|object|array  $input    The value to convert into an array
     * @param   array|string         $options  An array of additional options for the conversion.
     *                                         (LEGACY: a string containing the separator to split the string at)
     *                                         - separator (string) ",": The separator to split the string at.
     *                                         - convertTypes (bool) TRUE: values will automatically  be mapped into
     *                                         their PHP value type (int, float, true, false and null). To disable the
     *                                         type conversion set this option to FALSE.
     *                                         - strictNumerics (bool) FALSE: By default, numeric values with prefix
     *                                         (-/+) will be converted into integers/floats. If this flag is set to
     *                                         true, only digit values will be converted into integers/floats, those
     *                                         with prefixes will stay strings.
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayGeneratorException
     * @todo in the next major release "convertTypes" should be "false" by default.
     */
    public static function makeFromStringList(
        $input,
        $options = null
    ): array
    {
        $options = is_array($options) ? $options : ['separator' => $options ?? ','];
        
        return (new FromStringListGenerator())->generate($input, $options);
    }
    
    /**
     * Receives a string value and parses it as a csv into an array
     *
     * @param   string  $input          The csv string to parse
     * @param   bool    $firstLineKeys  Set to true if the first line of the csv are keys for all other rows
     * @param   string  $delimiter      The delimiter between multiple fields
     * @param   string  $quote          The enclosure or quoting tag
     *
     * @return array[]
     * @throws \Neunerlei\Arrays\ArrayGeneratorException
     */
    public static function makeFromCsv(
        $input,
        bool $firstLineKeys = false,
        string $delimiter = ',',
        string $quote = '"'
    ): array
    {
        return (new FromCsvGenerator())->generate(...func_get_args());
    }
    
    /**
     * Creates an array out of a json data string. Throws an exception if an error occurred!
     * Only works with json objects or arrays. Other values will throw an exception
     *
     * @param   string  $input
     * @param   array   $options  Additional configuration options for the decoding
     *                            - assoc bool (TRUE): By default objects are unserialized as associative arrays
     *                            - options int (0): Bitmask consisting of one or multiple of the JSON_ constants.
     *                            The behaviour of these constants is described on the JSON constants page.
     *                            JSON_THROW_ON_ERROR is set by default for all operations
     *                            - depth int (512): User specified recursion depth.
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayGeneratorException
     */
    public static function makeFromJson($input, array $options = []): array
    {
        return (new FromJsonGenerator())->generate(...func_get_args());
    }
    
    /**
     * Receives a xml-input and converts it into a multidimensional array
     *
     * @param   string|array|null|\DOMNode|\SimpleXMLElement  $input
     * @param   bool                                          $asAssocArray  If this is set to true the result object
     *                                                                       is
     *                                                                       converted to a more readable associative
     *                                                                       array. Be careful with this! There might
     *                                                                       be
     *                                                                       sideEffects, like changing paths when the
     *                                                                       result array has a changing number of
     *                                                                       nodes.
     *
     * @return array
     * @throws \Neunerlei\Arrays\ArrayGeneratorException
     */
    public static function makeFromXml($input, bool $asAssocArray = false): array
    {
        return (new FromXmlGenerator())->generate(...func_get_args());
    }
    
    /**
     * This method is basically a slightly adjusted clone of cakephp's xml::_toArray method
     * It recursively converts a given xml tree into an associative php array
     *
     * @see        https://github.com/cakephp/utility/blob/master/Xml.php
     *
     * The array will contain the tag, attributes text content and nodes recursively.
     *
     * @param   \SimpleXMLElement  $xml  The xml element to traverse
     * @param   array              $parentData
     * @param   string             $ns
     * @param   array              $namespaces
     *
     * @deprecated Will be removed in the next major release
     * @codeCoverageIgnore
     */
    protected static function xmlObjectToArray(
        SimpleXMLElement $xml,
        array &$parentData,
        string $ns,
        array $namespaces
    ): void
    {
        (new FromXmlGenerator())->legacyBridge(__FUNCTION__, [$xml, &$parentData, $ns, $namespaces]);
    }
    
    /**
     * Internal helper that is used to convert an xml result array to a
     * more readable associative array. Be careful with this! There might be sideEffects,
     * like changing paths when the result array has a changing number of nodes.
     *
     * @param   array  $xmlArray  The xml array to convert
     *
     * @return array
     *
     * @deprecated Will be removed in the next major release
     * @codeCoverageIgnore
     */
    protected static function xmlArrayToAssoc(array $xmlArray): array
    {
        return (new FromXmlGenerator())->legacyBridge(__FUNCTION__, func_get_args());
    }
}
