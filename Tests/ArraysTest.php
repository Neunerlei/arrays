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
 * Last modified: 2020.03.02 at 21:02
 */
declare(strict_types=1);

namespace Neunerlei\Arrays\Tests\Assets;

use Neunerlei\Arrays\ArrayDumper;
use Neunerlei\Arrays\ArrayException;
use Neunerlei\Arrays\ArrayGenerator;
use Neunerlei\Arrays\ArrayPaths;
use Neunerlei\Arrays\Arrays;
use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase {
	public function testInstanceCreation() {
		$this->assertInstanceOf(ArrayDumper::class, DummyArraysAdapter::makeInstance(Arrays::$dumperClass));
		$this->assertInstanceOf(ArrayGenerator::class, DummyArraysAdapter::makeInstance(Arrays::$generatorClass));
		$this->assertInstanceOf(ArrayPaths::class, DummyArraysAdapter::makeInstance(Arrays::$pathClass));
		$this->assertSame(DummyArraysAdapter::makeInstance(Arrays::$pathClass), DummyArraysAdapter::makeInstance(Arrays::$pathClass));
		$this->assertArrayHasKey(Arrays::$pathClass, DummyArraysAdapter::getInstances());
		$this->assertEquals(3, count(DummyArraysAdapter::getInstances()));
		
		// Check if the update works
		Arrays::$dumperClass = DummyDumper::class;
		$this->assertInstanceOf(DummyDumper::class, DummyArraysAdapter::makeInstance(Arrays::$dumperClass));
		$this->assertEquals(4, count(DummyArraysAdapter::getInstances()));
		
		DummyArraysAdapter::flushInstances();
	}
	
	public function _testIsAssociativeDataProvider() {
		return [
			[FALSE, []],
			[FALSE, [123, 234, 345, "asdf"]],
			[TRUE, ["foo" => "bar"]],
			[TRUE, ["foo" => "bar", 123, "asdf"]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testIsAssociativeDataProvider
	 */
	public function testIsAssociative($a, $b) {
		$this->assertEquals($a, Arrays::isAssociative($b));
	}
	
	public function _testIsSequentialDataProvider() {
		$list = [123, 234, 345, "asdf"];
		unset($list[2]);
		return [
			[FALSE, []],
			[TRUE, [123, 234, 345, "asdf"]],
			[FALSE, $list],
			[FALSE, ["foo" => "bar"]],
			[FALSE, ["foo" => "bar", 123, "asdf"]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testIsSequentialDataProvider
	 */
	public function testIsSequential($a, $b) {
		$this->assertEquals($a, Arrays::isSequential($b));
	}
	
	public function _testIsArrayListDataProvider() {
		return [
			[FALSE, ["asdf" => 1]],
			[FALSE, ["asdf", 123, 234]],
			[TRUE, ["asdf" => ["asdf"]]],
			[TRUE, [["asdf"], [123]]],
			[TRUE, [[], []]],
			[TRUE, [[]]],
			[TRUE, []],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testIsArrayListDataProvider
	 */
	public function testIsArrayList($a, $b) {
		$this->assertEquals($a, Arrays::isArrayList($b));
	}
	
	public function _testSortByStrLenDataProvider() {
		return [
			[["fooFooFoo", "fooFoo", "fooFoo", "bar"], ["bar", "fooFoo", "fooFooFoo", "fooFoo"]],
			[["bar", "fooFoo", "fooFooFoo"], ["fooFooFoo", "bar", "fooFoo"], TRUE],
			[["foo" => "fooFoo", "bar" => "bar"], ["bar" => "bar", "foo" => "fooFoo"]],
			[["bar" => "bar", "foo" => "fooFoo"], ["bar" => "bar", "foo" => "fooFoo"], TRUE],
		];
	}
	
	/**
	 * @param      $a
	 * @param      $b
	 * @param bool $c
	 *
	 * @dataProvider _testSortByStrLenDataProvider
	 */
	public function testSortByStrLen($a, $b, $c = FALSE) {
		$this->assertEquals($a, Arrays::sortByStrLen($b, $c));
	}
	
	public function _testSortByKeyStrLenDataProvider() {
		return [
			[["a" => "a", "aa" => "aa", "aaa" => "aaa"], ["aaa" => "aaa", "a" => "a", "aa" => "aa"]],
			[["a", "b", "c"], [0 => "a", 1 => "b", 2 => "c"]],
			[[0 => "a", 30 => "b", 500 => "c"], [0 => "a", 500 => "c", 30 => "b",]],
			[["aaa" => "aaa", "aa" => "aa", "a" => "a"], ["aaa" => "aaa", "a" => "a", "aa" => "aa"], TRUE],
			[["c", "a", "b"], [0 => "c", 1 => "a", 2 => "b"], TRUE],
			[[500 => "c", 30 => "b", 0 => "a"], [0 => "a", 500 => "c", 30 => "b",], TRUE],
		];
	}
	
	/**
	 * @param      $a
	 * @param      $b
	 * @param bool $c
	 *
	 * @dataProvider _testSortByKeyStrLenDataProvider
	 */
	public function testSortByKeyStrLen($a, $b, $c = FALSE) {
		$this->assertEquals($a, Arrays::sortByKeyStrLen($b, $c));
	}
	
	public function _testMergeDataProvider() {
		return [
			[["foo", "a", "b", "c"], [["foo"], ["a", "b", "c"]]],
			[["a", "b", "c"], [["a"], ["b", "c"]]],
			[[["a", "b"], "c"], [[["a"]], [["b"]], ["c"]]],
			
			[["a", "b", "c"], [["foo"], ["a", "b", "c"], "strictNumericMerge"]],
			[["a", "b", "c"], [["foo"], ["a", "b", "c"], "sn"]],
			
			[["foo", "a", "b", "c"], [["foo"], ["a", "b", "c"], "noNumericMerge"]],
			[["foo", "a", "b", "c"], [["foo"], ["a", "b", "c"], "nn"]],
			
			[["foo" => "bar"], [["foo" => "bar", "bar" => "baz"], ["bar" => "__UNSET"], "allowRemoval"]],
			[["foo" => "bar"], [["foo" => "bar", "bar" => "baz"], ["bar" => "__UNSET"], "r"]],
			[["foo" => "bar", "bar" => "baz"], [["foo" => "bar", "bar" => "baz"], ["baz" => "__UNSET"], "r"]],
			[["foo" => "bar", "bar" => "__UNSET"], [["foo" => "bar", "bar" => "baz"], ["bar" => "__UNSET"]]],
			
			[["a" => ["b" => ["c" => "c"], "d" => "d"]], [["a" => []], ["a" => ["b" => ["c" => "c"]]], ["a" => ["d" => "d"]],]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testMergeDataProvider
	 */
	public function testMerge($a, $b) {
		$this->assertEquals($a, Arrays::merge(...$b));
	}
	
	public function _testMergeFailDataProvider() {
		return [
			[["foo"]],
			[[[]]],
			[[NULL]],
		];
	}
	
	/**
	 * @param $a
	 *
	 * @dataProvider _testMergeFailDataProvider
	 */
	public function testMergeFail($a) {
		$this->expectException(ArrayException::class);
		Arrays::merge(...$a);
	}
	
	public function _testAttachDataProvider() {
		return [
			[["a", "b", "c"], [["a"], ["b"], ["c"]]],
			[["foo" => "foo", "bar" => "bar", 123], [["foo" => "foo"], ["bar" => "bar"], [123]]],
			[["foo" => "baz"], [["foo" => "bar"], ["foo" => "baz"]]],
			[["foo" => ["bar" => "bar"]], [["foo" => 123], ["foo" => ["bar" => "bar"]]]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testAttachDataProvider
	 */
	public function testAttach($a, $b) {
		$this->assertEquals($a, Arrays::attach(...$b));
	}
	
	public function _testAttachFailDataProvider() {
		return [
			[["foo"]],
			[[[]]],
			[[NULL]],
		];
	}
	
	/**
	 * @param $a
	 *
	 * @dataProvider _testAttachFailDataProvider
	 */
	public function testAttachFail($a) {
		$this->expectException(ArrayException::class);
		Arrays::attach(...$a);
	}
	
	public function _testRenameKeysDataProvider() {
		return [
			[["foo" => "bar"], ["bar" => "bar"], ["bar" => "foo"]],
			[["bar2" => "bar", "baz" => "foo"], ["bar" => "bar", "foo" => "foo"], ["bar" => "bar2", "foo" => "baz"]],
			[["foo" => "foo"], ["bar" => "bar", "foo" => "foo"], ["bar" => "foo"]],
			[["foo" => "bar", "baz" => "foo"], ["bar" => "bar", "foo" => "foo"], ["bar" => "foo", "foo" => "baz"]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 * @param $c
	 *
	 * @dataProvider _testRenameKeysDataProvider
	 */
	public function testRenameKeys($a, $b, $c) {
		$this->assertEquals($a, Arrays::renameKeys($b, $c));
	}
	
	public function _testInsertAtDataProvider() {
		return [
			[
				["foo" => "foo", "bar" => "bar"],
				[["foo" => "foo"], "baz", "bar", "bar"],
			],
			[
				["foo" => "foo", "bar" => "bar"],
				[["foo" => "foo"], "baz", "bar", "bar", TRUE],
			],
			[
				["foo" => "foo", "bar" => "bar"],
				[["foo" => "foo"], "foo", "bar", "bar"],
			],
			[
				["bar" => "bar", "foo" => "foo",],
				[["foo" => "foo"], "foo", "bar", "bar", TRUE],
			],
			[
				["bar" => "bar"],
				[[], "foo", "bar", "bar"],
			],
			[
				["bar" => "bar"],
				[[], "foo", "bar", "bar", TRUE],
			],
			[
				["foo", "bar", "baz"],
				[["foo", "baz"], 0, NULL, "bar"],
			],
			[
				["foo", "bar", "baz"],
				[["foo", "baz"], 1, NULL, "bar", TRUE],
			],
			[
				["foo" => ["bar", "baz"], "bar" => ["baz"]],
				[["bar" => 123, "foo" => ["bar", "baz"]], "foo", "bar", ["baz"]],
			],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testInsertAtDataProvider
	 */
	public function testInsertAt($a, $b) {
		$this->assertEquals($a, Arrays::insertAt(...$b));
	}
	
	public function _testShortenDataProvider() {
		return [
			[123, ["foo" => [["bar" => 123]]]],
			[123, [[[[[[[[123]]]]]]]]],
			[["foo" => "foo", "bar" => "bar"], [[[[[["foo" => "foo", "bar" => "bar"]]]]]]],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testShortenDataProvider
	 */
	public function testShorten($a, $b) {
		$this->assertEquals($a, Arrays::shorten($b));
	}
	
	public function _testGetSimilarKeyDataProvider() {
		return [
			["foo", ["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "foo"],
			["bar", ["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "bar"],
			["baz", ["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "baz"],
			["baz", ["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "barz"],
			["foo", ["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "fao"],
			[2, ["foo", "bar", "baz"], "fao"],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 * @param $c
	 *
	 * @dataProvider _testGetSimilarKeyDataProvider
	 */
	public function testGetSimilarKey($a, $b, $c) {
		$this->assertEquals($a, Arrays::getSimilarKey($b, $c));
	}
	
	public function _testSortByDataProvider() {
		$data = [
			"asdf" => [
				"key" => 2,
				"sub" => [
					"key" => 2,
				],
			],
			"cde"  => [
				"key" => 1,
				"sub" => [
					"key" => 3,
				],
			],
		];
		return [
			[
				["cde" => $data["cde"], "asdf" => $data["asdf"]],
				[$data, "key"],
			],
			[
				["asdf" => $data["asdf"], "cde" => $data["cde"]],
				[$data, "key", ["desc"]],
			],
			[
				["asdf" => $data["asdf"], "cde" => $data["cde"]],
				[$data, "sub.key"],
			],
			[
				["cde" => $data["cde"], "asdf" => $data["asdf"]],
				[$data, "sub.key", ["desc"]],
			],
			[
				["asdf" => $data["asdf"], "cde" => $data["cde"]],
				[$data, "sub-key", ["separator" => "-"]],
			],
			[
				["cde" => $data["cde"], "asdf" => $data["asdf"]],
				[$data, "sub-key", ["desc", "separator" => "-"]],
			],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testSortByDataProvider
	 */
	public function testSortBy($a, $b) {
		$this->assertEquals($a, Arrays::sortBy(...$b));
	}
	
	public function _testWithoutDataProvider() {
		return [
			[
				["foo" => TRUE],
				[["true" => TRUE, "foo" => TRUE], ["true"]],
			],
			[
				["foo" => 123, "bar" => 123],
				[["true" => TRUE, "foo" => 123, "bar" => 123, "false" => FALSE], ["true", "false"]],
			],
			[
				[["foo" => "foo"], ["foo" => "foo"]],
				[[["foo" => "foo", "bar" => "bar"], ["foo" => "foo", "bar" => "bar"]], ["*.bar"]],
			],
			[
				[["foo" => "foo"], ["foo" => "foo"]],
				[[["foo" => "foo", "bar" => "bar"], ["foo" => "foo", "bar" => "bar"]], [["*", "bar"]]],
			],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testWithoutDataProvider
	 */
	public function testWithout($a, $b) {
		$this->assertEquals($a, Arrays::without(...$b));
	}
	
	public function _testFlattenDataProvider() {
		return [
			[
				[
					"foo"     => 123,
					"bar.baz" => 234,
				],
				[["foo" => 123, "bar" => ["baz" => 234]]],
			],
			[
				[
					"foo"         => 123,
					"bar.baz.bar" => 234,
					"baz.bar.foo" => TRUE,
				],
				[["foo" => 123, "bar" => ["baz" => ["bar" => 234]], "baz" => ["bar" => ["foo" => TRUE]]]],
			],
			[
				[
					"foo"     => 123,
					"bar-baz" => 234,
				],
				[["foo" => 123, "bar" => ["baz" => 234]], ["separator" => "-"]],
			],
			[
				[
					"foo"      => 123,
					"bar.foo"  => "bar",
					"bar.true" => TRUE,
				],
				[["foo" => 123, "bar" => new DummyIterator()]],
			],
			[
				[
					"foo" => 123,
					"bar" => new DummyIterator(),
				],
				[["foo" => 123, "bar" => new DummyIterator()], ["arraysOnly"]],
			],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testFlattenDataProvider
	 */
	public function testFlatten($a, $b) {
		$this->assertEquals($a, Arrays::flatten(...$b));
	}
	
	public function _testUnflattenDataProvider() {
		return [
			[
				["foo" => 123, "bar" => ["baz" => 234]],
				[["foo" => 123, "bar.baz" => 234,]],
			],
			[
				["foo" => 123, "bar" => ["baz" => ["bar" => 234]], "baz" => ["bar" => ["foo" => TRUE]]],
				[["foo" => 123, "bar.baz.bar" => 234, "baz.bar.foo" => TRUE,]],
			],
			[
				["foo" => 123, "bar" => ["baz" => 234]],
				[["foo" => 123, "bar-baz" => 234,], ["separator" => "-"]],
			],
		];
	}
	
	/**
	 * @param $a
	 * @param $b
	 *
	 * @dataProvider _testUnflattenDataProvider
	 */
	public function testUnflatten($a, $b) {
		$this->assertEquals($a, Arrays::unflatten(...$b));
	}
	
	public function testMapRecursive() {
		$list = [
			"foo" => "bar",
			"bar" => [
				"baz" => [
					"foo" => 123,
					"bar" => "bar",
					"faz" => [
						"bar" => TRUE,
						"baz" => FALSE,
					],
				],
			],
		];
		
		$expectValues = [
			// Value, Key, Path
			["bar", "foo", ["foo"]],
			[123, "foo", ["bar", "baz", "foo"]],
			["bar", "bar", ["bar", "baz", "bar"]],
			[TRUE, "bar", ["bar", "baz", "faz", "bar"]],
			[FALSE, "baz", ["bar", "baz", "faz", "baz"]],
		];
		
		$c = 0;
		$result = Arrays::mapRecursive($list, function ($value, $key, $path) use (&$c, $expectValues) {
			$this->assertEquals($expectValues[$c++], [$value, $key, $path]);
			return 1;
		});
		$expected = [
			"foo" => 1,
			"bar" => [
				"baz" => [
					"foo" => 1,
					"bar" => 1,
					"faz" => [
						"bar" => 1,
						"baz" => 1,
					],
				],
			],
		];
		$this->assertEquals($expected, $result);
	}
}