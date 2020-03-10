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


use DOMNode;
use Iterator;
use SimpleXMLElement;
use stdClass;

class ArrayGenerator {
	/**
	 * Receives a xml-input and converts it into a multidimensional array
	 *
	 * @param string|array|null|\DOMNode|\SimpleXMLElement $input
	 * @param bool                                         $asAssocArray
	 *
	 * @return array
	 * @throws \Neunerlei\Arrays\ArrayGeneratorException
	 */
	public function fromXml($input, bool $asAssocArray = FALSE): array {
		if (is_array($input)) return $input;
		if (empty($input)) return [];
		
		// Convert xml string to an object
		if (is_string($input)) {
			if (stripos(trim($input), "<?xml") !== 0)
				$input = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $input;
			$savedState = libxml_use_internal_errors(TRUE);
			$input = simplexml_load_string($input);
			libxml_use_internal_errors($savedState);
			if ($input === FALSE) {
				foreach (libxml_get_errors() as $error)
					throw new ArrayGeneratorException("Failed to parse XML input: " . $error->message);
			}
		}
		
		// Convert xml objects into arrays
		if ($input instanceof DOMNode) $input = simplexml_import_dom($input);
		if ($input instanceof SimpleXMLElement) $result = $this->xmlObjectToArray($input);
		
		// Check if we failed
		if (!isset($result))
			throw new ArrayGeneratorException("The given input is not supported as XML array source!");
		
		// Convert to assoc array if required
		if (!$asAssocArray) return $result;
		
		// Convert the array
		return $this->xmlArrayToAssoc($result);
	}
	
	/**
	 * The method receives an object of sorts and converts it into a multidimensional array
	 *
	 * @param $input
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public function fromObject($input): array {
		if (is_array($input)) return $input;
		if (empty($input)) return [];
		if ($input instanceof DOMNode || $input instanceof SimpleXMLElement) return $this->fromXml($input);
		// Convert iterator and standard class
		if ($input instanceof Iterator || $input instanceof stdClass) {
			$out = [];
			foreach ($input as $k => $v) $out[$k] = $v;
			return $out;
		}
		if (is_object($input)) return get_object_vars($input);
		throw new ArrayGeneratorException("The given input is not supported as OBJECT array source!");
	}
	
	/**
	 * Receives a string list like: "1,asdf,foo, bar" which will be converted into [1, "asdf", "foo", "bar"]
	 * Note the automatic trimming and value conversion of numbers, TRUE, FALSE an null.
	 * By default the separator is ","
	 *
	 * @param string $input     The value to convert into an array
	 * @param string $separator The separator to split the string at
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public function fromStringList($input, string $separator = ","): array {
		if (is_array($input)) return $input;
		if (empty($input) && $input !== 0) return [];
		if (!is_string($input) && !is_numeric($input) && !(is_object($input) && method_exists($input, "__toString")))
			throw new ArrayGeneratorException("The given input " . gettype($input) . " is not supported as STRING array source!");
		$parts = preg_split("~(?<!\\\)" . preg_quote($separator, "~") . "~", trim((string)$input), -1, PREG_SPLIT_NO_EMPTY);
		
		return array_values(array_filter(array_map(function ($v) use ($separator) {
			$v = trim($v);
			$vLower = strtolower($v);
			if ($vLower === "null") return NULL;
			if ($vLower === "false") return FALSE;
			if ($vLower === "true") return TRUE;
			if (is_numeric($vLower)) return strpos($vLower, ".") !== FALSE ? ((float)$v) : ((int)$v);
			if (stripos($v, $separator) !== FALSE) return str_replace("\\" . $separator, $separator, $v);
			return $v;
		}, $parts), function ($v) {
			return $v !== "";
		}));
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
	public function fromCsv($input, bool $firstLineKeys = FALSE,
							string $delimiter = ",", string $quote = "\""): array {
		if (is_array($input)) return $input;
		if (empty($input)) return [];
		if (!is_string($input))
			throw new ArrayGeneratorException("The given input is not supported as CSV array source!");
		$lines = preg_split("/$\R?^/m", trim($input));
		if (!is_array($lines))
			throw new ArrayGeneratorException("Error while parsing CSV array source!");
		$keyLength = 0;
		if ($firstLineKeys) {
			$keys = array_shift($lines);
			$keys = str_getcsv($keys, $delimiter, $quote);
			$keys = array_map("trim", $keys);
			$keyLength = count($keys);
		}
		foreach ($lines as $ln => $line) {
			$line = str_getcsv($line, $delimiter, $quote);
			$line = array_map("trim", $line);
			// No keys
			if (!isset($keys)) {
				$lines[$ln] = $line;
				continue;
			}
			// Keys match
			if (count($line) === $keyLength) {
				$lines[$ln] = array_combine($keys, $line);
				continue;
			}
			// Apply key length to line
			$lines[$ln] = array_combine($keys, array_pad(array_slice($line, 0, $keyLength), $keyLength, NULL));
		}
		return $lines;
	}
	
	/**
	 * Creates an array out of a json data string.
	 * Only works with json objects or arrays. Other values will throw an exception
	 *
	 * @param $input
	 *
	 * @return array
	 * @throws ArrayGeneratorException
	 */
	public function fromJson($input): array {
		if (is_array($input)) return $input;
		if (empty($input)) return [];
		if (!is_string($input))
			throw new ArrayGeneratorException("The given input is not supported as JSON array source!");
		$input = trim($input);
		if ($input[0] !== "{" && $input[0] !== "[")
			throw new ArrayGeneratorException("The given input is a string, but has no array as JSON data, so its no supported array source!");
		$data = @json_decode($input, TRUE);
		if (JSON_ERROR_NONE !== json_last_error())
			Throw new ArrayGeneratorException("Error generating json: " . json_last_error_msg());
		return $data;
	}
	
	/**
	 * This method is basically a slightly adjusted clone of cakephp's xml::_toArray method
	 * It recursively converts a given xml tree into an associative php array
	 *
	 * @see https://github.com/cakephp/utility/blob/master/Xml.php
	 *
	 * The array will contain the tag, attributes text content and nodes recursively.
	 *
	 * @param \SimpleXMLElement $xml The xml element to traverse
	 * @param array             $parentData
	 * @param string|NULL       $ns
	 * @param array|NULL        $namespaces
	 *
	 * @return array
	 */
	protected function xmlObjectToArray(SimpleXMLElement $xml, array &$parentData = [], string $ns = NULL, array $namespaces = NULL) {
		if ($ns === NULL) $ns = "";
		if ($namespaces === NULL) $namespaces = array_keys(array_merge(["" => ""], $xml->getNamespaces(TRUE)));
		$data = [];
		foreach ($namespaces as $namespace) {
			foreach ($xml->attributes($namespace, TRUE) as $key => $value) {
				if (!empty($namespace)) $key = $namespace . ":" . $key;
				$data["@" . $key] = (string)$value;
			}
			foreach ($xml->children($namespace, TRUE) as $child)
				static::xmlObjectToArray($child, $data, $namespace, $namespaces);
		}
		$asString = trim((string)$xml);
		if (empty($data)) $data = ["content" => $asString];
		else if (strlen($asString) > 0) $data["content"] = $asString;
		if (!empty($ns)) $ns .= ":";
		$name = $ns . $xml->getName();
		$data = ["tag" => $name] + $data;
		$parentData[] = $data;
		return $parentData;
	}
	
	/**
	 * Internal helper that is used to convert an xml result array to a
	 * more readable associative array. Be careful with this! There might be sideEffects,
	 * like changing paths when the result array has a changing number of nodes.
	 *
	 * @param array $xmlArray The xml array to convert
	 *
	 * @return array
	 */
	protected function xmlArrayToAssoc(array $xmlArray): array {
		$assoc = [];
		foreach ($xmlArray as $k => $el) {
			if (!is_array($el)) continue;
			if (!isset($el["tag"])) dbge($el);
			$key = $el["tag"];
			if (is_string($key) && substr($key, 0, 1) === "@") continue;
			
			// Check if there is a static content.
			if (isset($el["content"])) {
				$assoc[$key][] = $el["content"];
				continue;
			}
			
			// Recursively convert the children to an assoc array
			$assoc[$key] = $this->xmlArrayToAssoc($el);
		}
		
		// Make sure we make the object as easy to read as possible
		// We will strip out all wrapper arrays that we don't need, when we only have a single child.
		$assoc = array_map(function ($el) {
			if (count($el) === 1 && !is_array(reset($el)) && is_numeric(key($el))) return reset($el);
			return $el;
		}, $assoc);
		
		// Done
		return $assoc;
	}
}