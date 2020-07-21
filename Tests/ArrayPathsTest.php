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
 * Last modified: 2020.03.02 at 20:54
 */

namespace Neunerlei\Arrays\Tests;

use Neunerlei\Arrays\Arrays;
use Neunerlei\Arrays\Tests\Assets\DummyArrayPathsAdapter;
use PHPUnit\Framework\TestCase;

class ArrayPathsTest extends TestCase
{
    
    public function _testParsePathDataProvider()
    {
        return [
            [["123"], "123"],
            [["123"], "123"],
            [["foo", "bar", "baz", "123"], ["foo", "bar", "baz", "123"]],
            [["foo", "bar", "baz", "123"], ["foo", "bar", "baz", 123]],
            [["foo", "bar", "baz"], "foo.bar.baz."],
            [["foo", "bar", "baz"], "foo.bar..baz"],
            [["foo", "bar", "baz"], "foo-bar-baz", "-"],
            
            // Test if cached path is resolved correctly
            [["foo", "bar", "baz"], "foo.bar.baz"],
            [["foo", "bar", "baz"], "foo.bar.baz"],
            [["foo", "bar", "baz"], "foo.bar.baz"],
            
            [["foo.bar", "baz"], "foo\\.bar.baz"],
            [["foo-bar", "baz"], "foo\\-bar-baz", "-"],
            [["[wild,wild2].*.horse.[carrot, orange]"], "\\[wild\\,wild2\\]\\.\\*\\.horse\\.\\[carrot\\, orange\\]"],
            [["foo", ["baz", "[bar]"]], "foo.[baz, \\[bar\\]]"],
            
            [["foo", ["bar", "baz", "foo"]], "foo.[bar,baz,foo]"],
            [["foo", ["bar", "baz", ["foo", "bar", "baz"]]], "foo.[bar,baz,foo.bar.baz]"],
            [[["wild", "wild2"], "*", "horse", ["carrot", "orange"]], "[wild,wild2].*.horse.[carrot, orange]"],
        ];
    }
    
    /**
     * @param           $a
     * @param           $b
     * @param   string  $c
     *
     * @dataProvider _testParsePathDataProvider
     */
    public function testParsePath($a, $b, $c = ".")
    {
        $this->assertEquals($a, Arrays::parsePath($b, $c));
    }
    
    public function _testParsePathWithInvalidDataDataProvider()
    {
        return [
            [function () { }],
            [["string", function () { }]],
            [["string", "int", 123, [["foo", function () { }]]]],
            ["foo.[bar,baz,foo"],
            ["[[[[[[]]]]]]]]]]]]"],
            ["foo.[bar,baz,foo.bar.baz, sub.[foo"],
        ];
    }
    
    /**
     * @dataProvider _testParsePathWithInvalidDataDataProvider
     */
    public function testParsePathWithInvalidData($v)
    {
        $this->expectException(\InvalidArgumentException::class);
        Arrays::parsePath($v);
    }
    
    public function _testMergePathsDataProvider()
    {
        return [
            [["foo", "bar", "bar", "baz"], "foo.bar", "bar.baz"],
            [["foo", "bar", "bar", "baz"], "foo.bar.", "bar.baz"],
            [["foo", "bar", "bar", "baz"], "foo,bar", "bar,baz", ","],
            [["foo", "bar", "bar", "baz"], "foo,bar", "bar-baz", ",", "-"],
            [["foo", "bar", "bar", "baz"], "foo.bar", ["bar", "baz"]],
            [["foo", "bar", "bar", "baz"], ["foo", "bar"], "bar.baz"],
            [["foo", "bar", "bar", "baz"], ["foo", "bar"], ["bar", "baz"]],
        ];
    }
    
    /**
     * @param           $a
     * @param           $b
     * @param           $c
     * @param   string  $d
     * @param   string  $e
     *
     * @dataProvider _testMergePathsDataProvider
     */
    public function testMergePaths($a, $b, $c, $d = ".", $e = null)
    {
        $this->assertEquals($a, Arrays::mergePaths($b, $c, $d, $e));
    }
    
    public function testInitWalkerStep()
    {
        $this->assertEquals([
            ["rumpel"],
            false,
            0 // ArrayPaths::KEY_TYPE_DEFAULT,
        ],
            DummyArrayPathsAdapter::getWalkerStep($this->getTree(), ["rumpel", "pumpel"]));
        
        $this->assertEquals([
            ["foo", "bar", "baz", "rumpel", "wild", "wild2"],
            false,
            1 // ArrayPaths::KEY_TYPE_WILDCARD,
        ],
            DummyArrayPathsAdapter::getWalkerStep($this->getTree(), ["*", "pumpel"]));
        
        $this->assertEquals([
            ["baz", ["rumpel", "grumpel"], ["rumpel", "foo"]],
            false,
            2 //ArrayPaths::KEY_TYPE_KEYS,
        ],
            DummyArrayPathsAdapter::getWalkerStep($this->getTree(),
                [["baz", ["rumpel", "grumpel"], ["rumpel", "foo"]], "pumpel"]));
    }
    
    /**
     * HAS PATH
     */
    public function testHasSimplePath()
    {
        $this->assertTrue(Arrays::hasPath($this->getTree(), "baz.2.1"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), "rumpel.pumpel.foo"));
        $this->assertFalse(Arrays::hasPath($this->getTree(), "rumpel.pumpel.foo2"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), ["rumpel", "pumpel", "foo"]));
        $this->assertFalse(Arrays::hasPath($this->getTree(), ["rumpel", "pumpel", "foo3"]));
    }
    
    public function testHasWildcardPath()
    {
        $this->assertTrue(Arrays::hasPath($this->getTree(), "wild.*.foo"));
        $this->assertFalse(Arrays::hasPath($this->getTree(), "rumpel.*.foo"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), ["rumpel", "pumpel", "foo"]));
        $this->assertTrue(Arrays::hasPath($this->getTree(), "wild.*.horse.carrot"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), ["wild", "*", "horse", "carrot"]));
    }
    
    public function testHasPathWithSubKeys()
    {
        $this->assertTrue(Arrays::hasPath($this->getTree(), "[foo,bar]"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), [["foo", "bar"]]));
        $this->assertFalse(Arrays::hasPath($this->getTree(), [["foo", "bar", "foz"]]));
        $this->assertTrue(Arrays::hasPath($this->getTree(), ["rumpel", ["pumpel", "grumpel"]]));
        $this->assertTrue(Arrays::hasPath($this->getTree(), ["rumpel", [["pumpel", "foo"], "grumpel"]]));
        $this->assertTrue(Arrays::hasPath($this->getTree(), "wild.*.horse.[carrot,saddle]"));
        $this->assertFalse(Arrays::hasPath($this->getTree(), "wild.*.horse.[carrot,saddle,rose]"));
        $this->assertTrue(Arrays::hasPath($this->getTree(), "[wild,wild2].*.foo"));
    }
    
    /**
     * GET PATH
     */
    public function testGetSimplePath()
    {
        $this->assertEquals("bar", Arrays::getPath($this->getTree(), "baz.2.1"));
        $this->assertEquals("pumpFoo", Arrays::getPath($this->getTree(), "rumpel.pumpel.foo"));
        $this->assertNull(Arrays::getPath($this->getTree(), "rumpel.pumpel.foo2"));
        $this->assertFalse(Arrays::getPath($this->getTree(), "rumpel.pumpel.foo2", false));
        $this->assertEquals("pumpFoo", Arrays::getPath($this->getTree(), ["rumpel", "pumpel", "foo"]));
        $this->assertNull(Arrays::getPath($this->getTree(), ["rumpel", "pumpel", "foo3"]));
    }
    
    public function testGetWildcardPath()
    {
        $this->assertEquals([123, 234], Arrays::getPath($this->getTree(), "wild.*.foo"));
        $this->assertEquals(["pumpel" => "pumpFoo", "foo" => 1, "grumpel" => 1],
            Arrays::getPath($this->getTree(), "rumpel.*.foo", 1));
        $this->assertEquals("pumpFoo", Arrays::getPath($this->getTree(), ["rumpel", "pumpel", "foo"]));
        $this->assertEquals([123, 562], Arrays::getPath($this->getTree(), "wild.*.horse.carrot"));
        $this->assertEquals([
            [
                "carrot" => 123,
                "stick"  => 234,
                "saddle" => 345,
            ],
            [
                "carrot" => 562,
                "stick"  => 678,
                "saddle" => 903,
            ],
        ], Arrays::getPath($this->getTree(), "wild.*.horse.*"));
    }
    
    public function testGetWithSubKeys()
    {
        $this->assertEquals(["bar" => 123, "foo" => "bar"], Arrays::getPath($this->getTree(), "[foo,bar]"));
        $this->assertEquals(["bar" => 123, "foo" => "bar"], Arrays::getPath($this->getTree(), [["foo", "bar"]]));
        $this->assertEquals(
            ["bar" => 123, "foo" => "bar", "foz" => null],
            Arrays::getPath($this->getTree(), [["foo", "bar", "foz"]]));
        
        // Those should all lead to the same result
        $r = [
            "grumpel" => 555,
            "pumpel"  => [
                "foo" => "pumpFoo",
                "bar" => "pumpBar",
            ],
        ];
        $this->assertEquals($r, Arrays::getPath($this->getTree(), ["rumpel", ["pumpel", "grumpel"]]));
        $this->assertEquals($r, Arrays::getPath($this->getTree(), "rumpel.[grumpel,pumpel.foo,pumpel.bar]"));
        $this->assertEquals($r, Arrays::getPath($this->getTree(), "rumpel.[grumpel,pumpel.*]"));
        $this->assertEquals($r, Arrays::getPath($this->getTree(), "rumpel.[grumpel,pumpel.[foo,bar]]"));
        
        $this->assertEquals(
            ["grumpel" => 555, "pumpel" => ["foo" => "pumpFoo"]],
            Arrays::getPath($this->getTree(), ["rumpel", [["pumpel", "foo"], "grumpel"]]));
        
        $this->assertEquals(
            [
                ["carrot" => 123, "saddle" => 345],
                ["carrot" => 562, "saddle" => 903],
            ],
            Arrays::getPath($this->getTree(), "wild.*.horse.[carrot,saddle]"));
        
        $this->assertEquals(
            [
                ["carrot" => 123, "rose" => null, "saddle" => 345],
                ["carrot" => 562, "rose" => null, "saddle" => 903],
            ],
            Arrays::getPath($this->getTree(), "wild.*.horse.[carrot,rose,saddle]"));
        
        $this->assertEquals([
            "wild"  => [123, 234],
            "wild2" => ["asdf", "bar", "baz"],
        ], Arrays::getPath($this->getTree(), "[wild,wild2].*.foo"));
    }
    
    /**
     * SET PATH
     */
    public function testSetSimplePath()
    {
        $this->assertEquals(["foo" => "bar"], Arrays::setPath([], "foo", "bar"));
        $this->assertEquals(["foo" => "bar", "bar" => "baz"], Arrays::setPath(["bar" => "baz"], "foo", "bar"));
        
        $e                      = $r = [
            "foo" => [
                "bar" => "baz",
                "baz" => [
                    "bar" => "foo",
                ],
            ],
        ];
        $e["foo"]["baz"]["foo"] = true;
        $this->assertEquals($e, Arrays::setPath($r, "foo.baz.foo", true));
        $this->assertEquals($e, Arrays::setPath($r, ["foo", "baz", "foo"], true));
    }
    
    public function testSetWildcardPath()
    {
        $e           = $r = [
            [
                "foo" => 123,
            ],
            [
                "foo" => 123,
            ],
        ];
        $e[0]["bar"] = true;
        $e[1]["bar"] = true;
        $this->assertEquals($e, Arrays::setPath($r, "*.bar", true));
        $this->assertEquals($e, Arrays::setPath($r, ["*", "bar"], true));
        
        $e                         = $r = [
            "key" => true,
            "sub" => [
                [
                    "foo" => 123,
                    "bar" => [],
                ],
                [
                    "foo" => 123,
                    "bar" => [],
                ],
            ],
        ];
        $e["sub"][0]["bar"]["key"] = 123;
        $e["sub"][1]["bar"]["key"] = 123;
        $this->assertEquals($e, Arrays::setPath($r, "sub.*.bar.key", 123));
        $this->assertEquals($e, Arrays::setPath($r, ["sub", "*", "bar", "key"], 123));
        
        $e    = $r = [
            [
                "foo" => 123,
            ],
            [
                "foo" => 123,
            ],
        ];
        $e[0] = false;
        $e[1] = false;
        $this->assertEquals($e, Arrays::setPath($r, "*", false));
        $this->assertEquals($e, Arrays::setPath($r, ["*"], false));
    }
    
    public function testSetPathWithSubKeys()
    {
        $e                               = $r = $this->getTree();
        $e["wild"][0]["horse"]["carrot"] = "foo";
        $e["wild"][0]["horse"]["saddle"] = "foo";
        $e["wild"][0]["horse"]["free"]   = "foo";
        $e["wild"][1]["horse"]["carrot"] = "foo";
        $e["wild"][1]["horse"]["saddle"] = "foo";
        $e["wild"][1]["horse"]["free"]   = "foo";
        $this->assertEquals($e, Arrays::setPath($r, "wild.*.horse.[carrot,saddle,free]", "foo"));
        $this->assertEquals($e, Arrays::setPath($r, ["wild", "*", "horse", ["carrot", "saddle", "free"]], "foo"));
        
        $e                            = $r = $this->getTree();
        $e["baz"]["pumpel"]["bar"]    = 123;
        $e["rumpel"]["pumpel"]["bar"] = 123;
        $this->assertEquals($e, Arrays::setPath($r, "[baz,rumpel].pumpel.bar", 123));
        
        $e                                = $r = $this->getTree();
        $e["wild"][0]["horse"]["carrot"]  = "orange";
        $e["wild"][1]["horse"]["carrot"]  = "orange";
        $e["wild"][0]["horse"]["orange"]  = "orange";
        $e["wild"][1]["horse"]["orange"]  = "orange";
        $e["wild2"][0]["horse"]["carrot"] = "orange";
        $e["wild2"][0]["horse"]["orange"] = "orange";
        $e["wild2"][1]["horse"]["carrot"] = "orange";
        $e["wild2"][1]["horse"]["orange"] = "orange";
        $e["wild2"][2]["horse"]["carrot"] = "orange";
        $e["wild2"][2]["horse"]["orange"] = "orange";
        $this->assertEquals($e, Arrays::setPath($r, "[wild,wild2].*.horse.[carrot, orange]", "orange"));
        
        $e                             = $r = $this->getTree();
        $e["baz"]["foo"]["bar"]["baz"] = true;
        $e["baz"]["foo"]["bar"]["bar"] = true;
        $this->assertEquals($e, Arrays::setPath($r, "baz.foo.[bar.baz, bar.bar]", true));
    }
    
    /**
     * REMOVE PATH
     */
    public function testRemoveSimplePath()
    {
        $this->assertEquals([], Arrays::removePath(["foo" => "bar"], "foo"));
        $this->assertEquals([], Arrays::removePath(["foo" => ["bar" => "baz"]], "foo"));
        $this->assertEquals([], Arrays::removePath(["foo" => ["bar" => "baz"]], "foo.bar"));
        $this->assertEquals(["foo" => []], Arrays::removePath(["foo" => ["bar" => "baz"]], "foo.bar", ["keepEmpty"]));
        
        $e = $r = $this->getTree();
        unset($e["baz"][2][1]);
        $this->assertEquals($e, Arrays::removePath($r, "baz.2.1"));
    }
    
    public function testRemoveWildcardPath()
    {
        $this->assertEquals([], Arrays::removePath(["foo" => "bar"], "*"));
        $this->assertEquals([], Arrays::removePath($this->getTree(), "*"));
        
        $e = $r = $this->getTree();
        unset($e["baz"][2][0]);
        $this->assertEquals($e, Arrays::removePath($r, "*.*.0"));
        
        $e = $r = $this->getTree();
        unset($e["wild2"]);
        unset($e["wild"][0]["foo"]);
        unset($e["wild"][1]["foo"]);
        $this->assertEquals($e, Arrays::removePath($r, "[wild,wild2].*.foo"));
        
        unset($e["rumpel"]["pumpel"]["foo"]);
        $this->assertEquals($e, Arrays::removePath($r, "*.*.foo"));
    }
    
    public function testRemoveWithSubKeys()
    {
        $e = $r = $this->getTree();
        unset($e["foo"]);
        unset($e["baz"][2]);
        $this->assertEquals($e, Arrays::removePath($r, "[foo,baz.2]"));
        
        $e = $r = $this->getTree();
        unset($e["foo"]);
        unset($e["baz"]);
        $this->assertEquals($e, Arrays::removePath($r, "[foo,baz.1,baz.2,baz.0]"));
        
        $e = $r = $this->getTree();
        unset($e["rumpel"]["pumpel"]);
        $this->assertEquals($e, Arrays::removePath($r, "rumpel.pumpel.[foo,bar]"));
        $this->assertEquals($e, Arrays::removePath($r, "rumpel.pumpel.*"));
        $this->assertEquals($e, Arrays::removePath($r, "rumpel.pumpel"));
    }
    
    /**
     * FILTER PATH
     */
    public function testFilter()
    {
        Arrays::filterPath([], "*.foo", function () {
            $this->fail("An empty array should never call the filter callback");
        });
        
        $list         = $this->getTree();
        $r            = Arrays::filterPath($this->getTree(), "wild.*",
            function ($v, $key, $path, $initialList) use ($list) {
                if ($path !== ["wild", 0] && $path !== ["wild", 1]) {
                    $this->fail("Filter path was not as expected!");
                }
                $this->assertEquals($list, $initialList);
                $this->assertIsArray($v);
                $this->assertArrayHasKey("foo", $v);
                
                return "asdf";
            });
        $e            = $this->getTree();
        $e["wild"][0] = "asdf";
        $e["wild"][1] = "asdf";
        $this->assertEquals($e, $r);
        
        $r                            = Arrays::filterPath($this->getTree(), "rumpel.[pumpel.foo,grumpel]",
            function ($v) {
                if ($v !== 555 && $v !== "pumpFoo") {
                    $this->fail("Filter value was not as expected!");
                }
                
                return true;
            });
        $e                            = $this->getTree();
        $e["rumpel"]["pumpel"]["foo"] = true;
        $e["rumpel"]["grumpel"]       = true;
        $this->assertEquals($e, $r);
    }
    
    /**
     * GET LIST
     */
    public function testGetListExamples()
    {
        // Example 1: Return a list of all "id" values
        $this->assertEquals(["234", "123"], Arrays::getList($this->getList(), "id"));
        $this->assertEquals(["234", "123"], Arrays::getList($this->getList(), ["id"]));
        
        // Example 2: Return a list of all "id" and "title" values
        $this->assertEquals(
            [
                ["id" => "234", "title" => "medium"],
                ["id" => "123", "title" => "apple"],
            ],
            Arrays::getList($this->getList(), ["id", "title"]));
        
        // Example 3: Return a list of all "title" values with their "id" as key
        $this->assertEquals(["234" => "medium", "123" => "apple"], Arrays::getList($this->getList(), "title", "id"));
        
        // Example 4: Path lookup and aliases for longer keys
        $this->assertEquals(
            [
                "234" => ["array.id" => "12", "myAlias" => "di"],
                "123" => ["array.id" => "23", "myAlias" => "pumpel"],
            ], Arrays::getList($this->getList(), ["array.id", "array.rumpel as myAlias"], "id"));
        
        // Example 5: Path lookup and default value for unknown keys
        $this->assertEquals(
            [
                "234" => ["array.id" => "12", "array.bar" => "baz"],
                "123" => ["array.id" => "23", "array.bar" => null],
            ], Arrays::getList($this->getList(), ["array.id", "array.bar"], "id"));
        
        $this->assertEquals(
            [
                "234" => ["array.id" => "12", "array.bar" => "baz", "baz" => true, "baz2" => true],
                "123" => ["array.id" => "23", "array.bar" => true, "baz" => true, "baz2" => true],
            ], Arrays::getList($this->getList(), ["array.id", "array.bar", "baz", "array.baz as baz2"], "id",
            ["default" => true]));
        
        // Example 6: Keep the rows identical but use a column value as key in the result array
        $r = ["234" => $this->getList()[0], "123" => $this->getList()[1]];
        $this->assertEquals($r, Arrays::getList($this->getList(), null, "id"));
        
        // Example 7: Dealing with path based key lookups
        $r = ["12" => "234", "23" => "123"];
        $this->assertEquals($r, Arrays::getList($this->getList(), "id", "array.id"));
    }
    
    public function testGetListInvalidValueKeys()
    {
        $this->expectException(\InvalidArgumentException::class);
        Arrays::getList([], new \stdClass());
    }
    
    protected function getList(): array
    {
        return [
            [
                "id"    => "234",
                "title" => "medium",
                "asdf"  => "asdf",
                "array" => [
                    "id"     => "12",
                    "rumpel" => "di",
                    "bar"    => "baz",
                ],
            ],
            [
                "id"    => "123",
                "title" => "apple",
                "asdf"  => "asdf",
                "array" => [
                    "id"     => "23",
                    "rumpel" => "pumpel",
                    "foo"    => "bar",
                ],
            ],
        ];
    }
    
    protected function getTree(): array
    {
        return [
            "foo"    => "bar",
            "bar"    => 123,
            "baz"    => [
                123,
                234,
                [
                    "foo",
                    "bar",
                ],
            ],
            "rumpel" => [
                "pumpel"  => [
                    "foo" => "pumpFoo",
                    "bar" => "pumpBar",
                ],
                "grumpel" => 555,
                "foo"     => 222,
            ],
            "wild"   => [
                [
                    "foo"   => 123,
                    "horse" => [
                        "carrot" => 123,
                        "stick"  => 234,
                        "saddle" => 345,
                    ],
                ],
                [
                    "foo"   => 234,
                    "horse" => [
                        "carrot" => 562,
                        "stick"  => 678,
                        "saddle" => 903,
                    ],
                ],
            ],
            "wild2"  => [
                ["foo" => "asdf"],
                ["foo" => "bar"],
                ["foo" => "baz"],
            ],
        ];
    }
}
