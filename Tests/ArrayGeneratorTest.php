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
 * Last modified: 2020.03.09 at 22:27
 */
declare(strict_types=1);

namespace Neunerlei\Arrays\Tests\Assets;


use Neunerlei\Arrays\ArrayGeneratorException;
use Neunerlei\Arrays\Arrays;
use PHPUnit\Framework\TestCase;

class ArrayGeneratorTest extends TestCase {
	
	public function _testFromXmlDataProvider() {
		$xml = "<body><node><subNode>Hello</subNode><otherSubNode>123</otherSubNode></node><node><subNode>Hello, from me too!</subNode></node></body>";
		$xmlExpected = [
			[
				"tag" => "body",
				[
					"tag" => "node",
					[
						"tag"     => "subNode",
						"content" => "Hello",
					],
					[
						"tag"     => "otherSubNode",
						"content" => "123",
					],
				],
				[
					"tag" => "node",
					[
						"tag"     => "subNode",
						"content" => "Hello, from me too!",
					],
				],
			],
		];
		return [
			[["foo" => 123], ["foo" => 123]],
			[$xmlExpected, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xml],
			[$xmlExpected, $xml],
			[$xmlExpected, new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xml)],
			[$xmlExpected, dom_import_simplexml(new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xml))],
			[[
				 [
					 "tag" => "body",
					 [
						 "tag"           => "node",
						 "@data-my-attr" => "foo-bar",
						 "content"       => "Baz",
					 ],
				 ],
			 ], "<body><node data-my-attr='foo-bar'>Baz</node></body>",
			],
			[[], ""],
			[[], NULL],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testFromXmlDataProvider
	 */
	public function testFromXml($a, $b) {
		$this->assertEquals($a, Arrays::makeFromXml($b));
	}
	
	public function _testFromXmlFailDataProvider(): array {
		return [
			[new \stdClass()],
			[123],
			["<body><tag><tag2></tag>"],
		];
	}
	
	/**
	 * @dataProvider _testFromXmlFailDataProvider
	 */
	public function testFromXmlFail($v) {
		$this->expectException(ArrayGeneratorException::class);
		Arrays::makeFromXml($v);
	}
	
	public function testFromXmlAsAssoc() {
		$xml = "<body><node><subNode>Hello</subNode><otherSubNode>123</otherSubNode></node><node><subNode>Hello, from me too!</subNode></node></body>";
		$this->assertEquals(["body" => [
			"node" => [
				"subNode" => "Hello, from me too!",
			],
		],
		], Arrays::makeFromXml($xml, TRUE));
		$xml = "<body><foo><subNode>Hello</subNode><otherSubNode>123</otherSubNode></foo><bar><subNode>Hello, from me too!</subNode></bar></body>";
		$this->assertEquals(["body" => [
			"foo" => [
				"subNode"      => "Hello",
				"otherSubNode" => "123",
			],
			"bar" => [
				"subNode" => "Hello, from me too!",
			],
		],
		], Arrays::makeFromXml($xml, TRUE));
	}
	
	public function _testFromObjectDataProvider() {
		return [
			[["foo" => TRUE], ["foo" => TRUE]],
			[[], ""],
			[[], NULL],
			[["foo" => "bar", "bar" => TRUE], (object)["foo" => "bar", "bar" => TRUE]],
			[[
				 [
					 "tag"     => "body",
					 "content" => "foo!",
				 ],
			 ], new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><body>foo!</body>"),
			],
			[[
				 [
					 "tag"     => "body",
					 "content" => "foo!",
				 ],
			 ], dom_import_simplexml(new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><body>foo!</body>")),
			],
			[["foo" => "bar", "true" => TRUE], new DummyIterator()],
			[["foo" => TRUE, "bar" => "baz"], new DummyClass()],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testFromObjectDataProvider
	 */
	public function testFromObject($a, $b) {
		$this->assertEquals($a, Arrays::makeFromObject($b));
	}
	
	public function _testFromStringListDataProvider() {
		return [
			[[], ""],
			[[], NULL],
			[[0], 0],
			[[123.23], 123.23],
			[[123.23], "123.23"],
			[["foo", "bar", "baz"], new DummyToString()],
			[["foo", "bar", "baz"], "foo , bar,    baz"],
			[[TRUE, FALSE, TRUE, 1, 0, NULL, 123], "true,FALSE,TRUE, 1, , 0,  NULL,, 123  "],
			[["foo,bar"], "foo\\,bar"],
			[["foo", "bar", "baz"], "foo-bar-baz", "-"],
			[["fooTrue", "fooNull"], "fooTrue,fooNull"],
		];
	}
	
	/**
	 * @param        $a
	 * @param        $b
	 * @param string $sep
	 *
	 * @dataProvider _testFromStringListDataProvider
	 */
	public function testFromStringList($a, $b, $sep = ",") {
		$this->assertEquals($a, Arrays::makeFromStringList($b, $sep));
	}
	
	
	public function _testFromStringListFailDataProvider() {
		return [
			[new \stdClass()],
			[new DummyClass()],
			[new DummyIterator()],
		];
	}
	
	/**
	 * @param $v
	 *
	 * @dataProvider _testFromStringListFailDataProvider
	 */
	public function testFromStringListFail($v) {
		$this->expectException(ArrayGeneratorException::class);
		Arrays::makeFromStringList($v);
	}
	
	public function _testFromCsvDataProvider() {
		return [
			[[], ""],
			[[], NULL],
			[[["a", "b", "c"]], "a,b,c"],
			[[["a", "b", "c"], ["d", "e", "f"]], "a,b,c" . PHP_EOL . "d,e,f"],
			[[["a", "b", "c"], ["d", "e", "f"]], "a, b, c" . PHP_EOL . "d, e,f  "],
			[[["foo" => "bar", "bar" => "baz"]], "foo,bar" . PHP_EOL . "bar,baz", TRUE],
			[[["a", "b", "c"], ["d", "e", "f"]], "a,b,\"c\"" . PHP_EOL . "d,\"e\",f"],
			[[["a", "b", "1,2"], ["d", "e", "f"]], "a,b,\"1,2\"" . PHP_EOL . "d,\"e\",f"],
			[[["foo" => "bar", "bar" => "baz", "faz" => NULL]], "foo,bar,faz" . PHP_EOL . "bar,baz", TRUE],
			[[["foo" => "bar", "bar" => "baz", "faz" => "faz"]], "foo,bar,faz" . PHP_EOL . "bar,baz,faz,raz", TRUE],
			[[["a", "b", "c"], ["d", "e", "f"]], "a	b	c" . PHP_EOL . "d	e	f", FALSE, "	"],
			[[["a", "b", "c"], ["d", "e", "f"]], "a,b,'c'" . PHP_EOL . "d,'e',f", FALSE, ",", "'"],
		];
	}
	
	/**
	 * @param        $a
	 * @param        $b
	 * @param bool   $c
	 * @param string $d
	 * @param string $e
	 *
	 * @dataProvider _testFromCsvDataProvider
	 */
	public function testFromCsv($a, $b, $c = FALSE, $d = ",", $e = "\"") {
		$this->assertEquals($a, Arrays::makeFromCsv($b, $c, $d, $e));
	}
	
	public function _testFromCsvFailDataProvider() {
		return [
			[123],
			[new DummyClass()],
			[new DummyToString()],
		];
	}
	
	/**
	 * @param $a
	 *
	 * @dataProvider _testFromCsvFailDataProvider
	 */
	public function testFromCsvFail($a) {
		$this->expectException(ArrayGeneratorException::class);
		Arrays::makeFromCsv($a);
	}
	
	public function _testFromJsonDataProvider() {
		return [
			[[], ""],
			[[], NULL],
			[[123, "foo", "bar"], "[123,\"foo\",\"bar\"]"],
			[["foo" => "bar", "bar" => "baz"], "{\"foo\":\"bar\",\"bar\":\"baz\"}"],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testFromJsonDataProvider
	 */
	public function testFromJson($a, $b) {
		$this->assertEquals($a, Arrays::makeFromJson($b));
	}
	
	public function _testFromJsonFailDataProvider() {
		return [
			["123"],
			["\"123\""],
			["NULL"],
			[new DummyClass()],
			["{\"foo\":bar\"}"],
		];
	}
	
	/**
	 * @param $a
	 *
	 * @dataProvider _testFromJsonFailDataProvider
	 */
	public function testFromJsonFail($a) {
		$this->expectException(ArrayGeneratorException::class);
		Arrays::makeFromJson($a);
	}
}