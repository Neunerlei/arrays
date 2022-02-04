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


namespace Neunerlei\Arrays\Features\Generator;


use DOMNode;
use InvalidArgumentException;
use Neunerlei\Arrays\ArrayGeneratorException;
use SimpleXMLElement;

class FromXmlGenerator
{
    public function generate($input, bool $asAssocArray = false): array
    {
        if (is_array($input)) {
            return $input;
        }
        
        if (empty($input)) {
            return [];
        }
        
        if (is_string($input)) {
            if (stripos(trim($input), '<?xml') !== 0) {
                $input = '<?xml version="1.0" encoding="UTF-8"?>' . $input;
            }
            
            $savedState = libxml_use_internal_errors(true);
            $input = simplexml_load_string($input);
            $errors = libxml_get_errors();
            
            libxml_use_internal_errors($savedState);
            if ($input === false || ! empty($errors)) {
                throw new ArrayGeneratorException('Failed to parse XML input: ' . reset($errors)->message);
            }
        }
        
        if ($input instanceof DOMNode) {
            $input = simplexml_import_dom($input);
        }
        if ($input instanceof SimpleXMLElement) {
            $result = [];
            $this->xmlObjectToArray($input, $result, '', array_keys(
                array_merge(['' => ''], $input->getNamespaces(true))
            ));
        }
        
        if (! isset($result)) {
            throw new ArrayGeneratorException('The given input is not supported as XML array source!');
        }
        
        if (! $asAssocArray) {
            return $result;
        }
        
        return $this->xmlArrayToAssoc($result);
    }
    
    /**
     * Helper to access the methods from the old base class
     *
     * @param   string  $method
     * @param   array   $args
     *
     * @return mixed
     * @deprecated temporary solution until the next major release
     * @codeCoverageIgnore
     */
    public function legacyBridge(string $method, array $args)
    {
        switch ($method) {
            case 'xmlObjectToArray':
            case 'xmlArrayToAssoc':
                return $this->$method(...$args);
            default:
                throw new InvalidArgumentException('The given method: "' . $method . '" is not accessible!');
        }
    }
    
    /**
     * This method is basically a slightly adjusted clone of cakephp's xml::_toArray method
     * It recursively converts a given xml tree into an associative php array
     *
     * @see https://github.com/cakephp/utility/blob/master/Xml.php
     *
     * The array will contain the tag, attributes text content and nodes recursively.
     *
     * @param   \SimpleXMLElement  $xml  The xml element to traverse
     * @param   array              $parentData
     * @param   string             $ns
     * @param   array              $namespaces
     */
    protected function xmlObjectToArray(
        SimpleXMLElement $xml,
        array &$parentData,
        string $ns,
        array $namespaces
    ): void
    {
        $data = [];
        foreach ($namespaces as $namespace) {
            foreach ($xml->attributes($namespace, true) as $key => $value) {
                if (! empty($namespace)) {
                    $key = $namespace . ':' . $key;
                }
                $data['@' . $key] = (string)$value;
            }
            foreach ($xml->children($namespace, true) as $child) {
                $this->xmlObjectToArray($child, $data, $namespace, $namespaces);
            }
        }
        $asString = trim((string)$xml);
        if (empty($data)) {
            $data = ['content' => $asString];
        } elseif ($asString !== '') {
            $data['content'] = $asString;
        }
        if (! empty($ns)) {
            $ns .= ':';
        } elseif (! empty($namespaces) && count($xml->getNamespaces()) === 1) {
            $nsl = $xml->getNamespaces();
            $ns = key($nsl) . ':';
        }
        $name = $ns . $xml->getName();
        $data = ['tag' => $name] + $data;
        $parentData[] = $data;
    }
    
    /**
     * Internal helper that is used to convert an xml result array to a
     * more readable associative array. Be careful with this! There might be sideEffects,
     * like changing paths when the result array has a changing number of nodes.
     *
     * @param   array  $xmlArray  The xml array to convert
     *
     * @return array
     */
    protected function xmlArrayToAssoc(array $xmlArray): array
    {
        $assoc = [];
        foreach ($xmlArray as $k => $el) {
            if (! is_array($el)) {
                continue;
            }
            $key = $el['tag'];
            
            // Check if there is a static content.
            if (isset($el['content'])) {
                $assoc[$key][] = $el['content'];
                continue;
            }
            
            // Recursively convert the children to an assoc array
            $assoc[$key] = $this->xmlArrayToAssoc($el);
        }
        
        // Make sure we make the object as easy to read as possible
        // We will strip out all wrapper arrays that we don't need, when we only have a single child.
        $assoc = array_map(static function ($el) {
            if (count($el) === 1 && ! is_array(reset($el)) && is_numeric(key($el))) {
                return reset($el);
            }
            
            return $el;
        }, $assoc);
        
        // Done
        return $assoc;
    }
}
