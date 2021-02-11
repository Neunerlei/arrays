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

class ArraysTest extends TestCase
{
    public function testInstanceCreation()
    {
        self::assertInstanceOf(ArrayDumper::class, DummyArraysAdapter::makeInstance(Arrays::$dumperClass));
        self::assertInstanceOf(ArrayGenerator::class, DummyArraysAdapter::makeInstance(Arrays::$generatorClass));
        self::assertInstanceOf(ArrayPaths::class, DummyArraysAdapter::makeInstance(Arrays::$pathClass));
        self::assertSame(DummyArraysAdapter::makeInstance(Arrays::$pathClass),
            DummyArraysAdapter::makeInstance(Arrays::$pathClass));
        self::assertArrayHasKey(Arrays::$pathClass, DummyArraysAdapter::getInstances());
        self::assertEquals(3, count(DummyArraysAdapter::getInstances()));

        // Check if the update works
        Arrays::$dumperClass = DummyDumper::class;
        self::assertInstanceOf(DummyDumper::class, DummyArraysAdapter::makeInstance(Arrays::$dumperClass));
        self::assertEquals(4, count(DummyArraysAdapter::getInstances()));

        DummyArraysAdapter::flushInstances();
    }

    public function testInstanceCreationFailure()
    {
        $this->expectException(ArrayException::class);
        DummyArraysAdapter::makeInstance(NonExistentClass::class);
    }

    public function _testIsAssociativeDataProvider()
    {
        return [
            [false, []],
            [false, [123, 234, 345, 'asdf']],
            [true, ['foo' => 'bar']],
            [true, ['foo' => 'bar', 123, 'asdf']],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testIsAssociativeDataProvider
     */
    public function testIsAssociative($a, $b)
    {
        self::assertEquals($a, Arrays::isAssociative($b));
    }

    public function _testIsSequentialDataProvider()
    {
        $list = [123, 234, 345, 'asdf'];
        unset($list[2]);

        return [
            [false, []],
            [true, [123, 234, 345, 'asdf']],
            [false, $list],
            [false, ['foo' => 'bar']],
            [false, ['foo' => 'bar', 123, 'asdf']],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testIsSequentialDataProvider
     */
    public function testIsSequential($a, $b)
    {
        self::assertEquals($a, Arrays::isSequential($b));
    }

    public function _testIsArrayListDataProvider()
    {
        return [
            [false, ['asdf' => 1]],
            [false, ['asdf', 123, 234]],
            [true, ['asdf' => ['asdf']]],
            [true, [['asdf'], [123]]],
            [true, [[], []]],
            [true, [[]]],
            [true, []],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testIsArrayListDataProvider
     */
    public function testIsArrayList($a, $b)
    {
        self::assertEquals($a, Arrays::isArrayList($b));
    }

    public function _testSortByStrLenDataProvider()
    {
        return [
            [['fooFooFoo', 'fooFoo', 'fooFoo', 'bar'], ['bar', 'fooFoo', 'fooFooFoo', 'fooFoo']],
            [['bar', 'fooFoo', 'fooFooFoo'], ['fooFooFoo', 'bar', 'fooFoo'], true],
            [['foo' => 'fooFoo', 'bar' => 'bar'], ['bar' => 'bar', 'foo' => 'fooFoo']],
            [['bar' => 'bar', 'foo' => 'fooFoo'], ['bar' => 'bar', 'foo' => 'fooFoo'], true],
        ];
    }

    /**
     * @param         $a
     * @param         $b
     * @param   bool  $c
     *
     * @dataProvider _testSortByStrLenDataProvider
     */
    public function testSortByStrLen($a, $b, $c = false)
    {
        self::assertEquals($a, Arrays::sortByStrLen($b, $c));
    }

    public function _testSortByKeyStrLenDataProvider()
    {
        return [
            [['a' => 'a', 'aa' => 'aa', 'aaa' => 'aaa'], ['aaa' => 'aaa', 'a' => 'a', 'aa' => 'aa']],
            [['a', 'b', 'c'], [0 => 'a', 1 => 'b', 2 => 'c']],
            [[0 => 'a', 30 => 'b', 500 => 'c'], [0 => 'a', 500 => 'c', 30 => 'b',]],
            [['aaa' => 'aaa', 'aa' => 'aa', 'a' => 'a'], ['aaa' => 'aaa', 'a' => 'a', 'aa' => 'aa'], true],
            [['c', 'a', 'b'], [0 => 'c', 1 => 'a', 2 => 'b'], true],
            [[500 => 'c', 30 => 'b', 0 => 'a'], [0 => 'a', 500 => 'c', 30 => 'b',], true],
        ];
    }

    /**
     * @param         $a
     * @param         $b
     * @param   bool  $c
     *
     * @dataProvider _testSortByKeyStrLenDataProvider
     */
    public function testSortByKeyStrLen($a, $b, $c = false)
    {
        self::assertEquals($a, Arrays::sortByKeyStrLen($b, $c));
    }

    public function _testMergeDataProvider()
    {
        return [
            [['foo', 'a', 'b', 'c'], [['foo'], ['a', 'b', 'c']]],
            [['a', 'b', 'c'], [['a'], ['b', 'c']]],
            [[['a', 'b'], 'c'], [[['a']], [['b']], ['c']]],

            [['a', 'b', 'c'], [['foo'], ['a', 'b', 'c'], 'strictNumericMerge']],
            [['a', 'b', 'c'], [['foo'], ['a', 'b', 'c'], 'sn']],

            [['foo', 'a', 'b', 'c'], [['foo'], ['a', 'b', 'c'], 'noNumericMerge']],
            [['foo', 'a', 'b', 'c'], [['foo'], ['a', 'b', 'c'], 'nn']],

            [['foo' => 'bar'], [['foo' => 'bar', 'bar' => 'baz'], ['bar' => '__UNSET'], 'allowRemoval']],
            [['foo' => 'bar'], [['foo' => 'bar', 'bar' => 'baz'], ['bar' => '__UNSET'], 'r']],
            [['foo' => 'bar', 'bar' => 'baz'], [['foo' => 'bar', 'bar' => 'baz'], ['baz' => '__UNSET'], 'r']],
            [['foo' => 'bar', 'bar' => '__UNSET'], [['foo' => 'bar', 'bar' => 'baz'], ['bar' => '__UNSET']]],
            [['a' => []], [['a' => []], ['a' => ['b' => '__UNSET']], 'r']],

            [
                ['a' => ['b' => ['c' => 'c'], 'd' => 'd']],
                [['a' => []], ['a' => ['b' => ['c' => 'c']]], ['a' => ['d' => 'd']],],
            ],

            [['foo'], [[], ['foo']]],
            [['foo' => 'bar'], [[], ['foo' => 'bar']]],
            [['bar'], [['bar'], []]],
            [['bar' => 'bar'], [['bar' => 'bar'], []]],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testMergeDataProvider
     */
    public function testMerge($a, $b)
    {
        self::assertEquals($a, Arrays::merge(...$b));
    }

    public function _testMergeFailDataProvider()
    {
        return [
            [['foo']],
            [[[]]],
            [[null]],
        ];
    }

    /**
     * @param $a
     *
     * @dataProvider _testMergeFailDataProvider
     */
    public function testMergeFail($a)
    {
        $this->expectException(ArrayException::class);
        Arrays::merge(...$a);
    }

    public function _testAttachDataProvider()
    {
        return [
            [['a', 'b', 'c'], [['a'], ['b'], ['c']]],
            [['foo' => 'foo', 'bar' => 'bar', 123], [['foo' => 'foo'], ['bar' => 'bar'], [123]]],
            [['foo' => 'baz'], [['foo' => 'bar'], ['foo' => 'baz']]],
            [['foo' => ['bar' => 'bar']], [['foo' => 123], ['foo' => ['bar' => 'bar']]]],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testAttachDataProvider
     */
    public function testAttach($a, $b)
    {
        self::assertEquals($a, Arrays::attach(...$b));
    }

    public function _testAttachFailDataProvider()
    {
        return [
            [['foo']],
            [[[]]],
            [[null]],
            [[null, 'foo']],
        ];
    }

    /**
     * @param $a
     *
     * @dataProvider _testAttachFailDataProvider
     */
    public function testAttachFail($a)
    {
        $this->expectException(ArrayException::class);
        Arrays::attach(...$a);
    }

    public function _testRenameKeysDataProvider()
    {
        return [
            [['foo' => 'bar'], ['bar' => 'bar'], ['bar' => 'foo']],
            [['bar2' => 'bar', 'baz' => 'foo'], ['bar' => 'bar', 'foo' => 'foo'], ['bar' => 'bar2', 'foo' => 'baz']],
            [['foo' => 'foo'], ['bar' => 'bar', 'foo' => 'foo'], ['bar' => 'foo']],
            [['foo' => 'bar', 'baz' => 'foo'], ['bar' => 'bar', 'foo' => 'foo'], ['bar' => 'foo', 'foo' => 'baz']],
        ];
    }

    /**
     * @param $a
     * @param $b
     * @param $c
     *
     * @dataProvider _testRenameKeysDataProvider
     */
    public function testRenameKeys($a, $b, $c)
    {
        self::assertEquals($a, Arrays::renameKeys($b, $c));
    }

    public function _testInsertAtDataProvider()
    {
        return [
            [
                ['foo' => 'foo', 'bar' => 'bar'],
                [['foo' => 'foo'], 'baz', 'bar', 'bar'],
            ],
            [
                ['foo' => 'foo', 'bar' => 'bar'],
                [['foo' => 'foo'], 'baz', 'bar', 'bar', true],
            ],
            [
                ['foo' => 'foo', 'bar' => 'bar'],
                [['foo' => 'foo'], 'foo', 'bar', 'bar'],
            ],
            [
                ['bar' => 'bar', 'foo' => 'foo',],
                [['foo' => 'foo'], 'foo', 'bar', 'bar', true],
            ],
            [
                ['bar' => 'bar'],
                [[], 'foo', 'bar', 'bar'],
            ],
            [
                ['bar' => 'bar'],
                [[], 'foo', 'bar', 'bar', true],
            ],
            [
                ['foo', 'bar', 'baz'],
                [['foo', 'baz'], 0, null, 'bar'],
            ],
            [
                ['foo', 'bar', 'baz'],
                [['foo', 'baz'], 1, null, 'bar', true],
            ],
            [
                ['foo' => ['bar', 'baz'], 'bar' => ['baz']],
                [['bar' => 123, 'foo' => ['bar', 'baz']], 'foo', 'bar', ['baz']],
            ],
            [
                ['baz', 'foo', 'bar'],
                [['foo', 'bar'], '0', null, 'baz', true],
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testInsertAtDataProvider
     */
    public function testInsertAt($a, $b)
    {
        self::assertEquals($a, Arrays::insertAt(...$b));
    }

    public function _testShortenDataProvider()
    {
        return [
            [123, ['foo' => [['bar' => 123]]]],
            [123, [[[[[[[[123]]]]]]]]],
            [['foo' => 'foo', 'bar' => 'bar'], [[[[[['foo' => 'foo', 'bar' => 'bar']]]]]]],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testShortenDataProvider
     */
    public function testShorten($a, $b)
    {
        self::assertEquals($a, Arrays::shorten($b));
    }

    public function _testGetSimilarKeyDataProvider()
    {
        return [
            ['foo', ['foo' => true, 'bar' => true, 'baz' => true], 'foo'],
            ['bar', ['foo' => true, 'bar' => true, 'baz' => true], 'bar'],
            ['baz', ['foo' => true, 'bar' => true, 'baz' => true], 'baz'],
            ['baz', ['foo' => true, 'bar' => true, 'baz' => true], 'barz'],
            ['foo', ['foo' => true, 'bar' => true, 'baz' => true], 'fao'],
            [2, ['foo', 'bar', 'baz'], 'fao'],
            [null, [], 'foo'],
        ];
    }

    /**
     * @param $a
     * @param $b
     * @param $c
     *
     * @dataProvider _testGetSimilarKeyDataProvider
     */
    public function testGetSimilarKey($a, $b, $c)
    {
        self::assertEquals($a, Arrays::getSimilarKey($b, $c));
    }

    public function _testSortByDataProvider()
    {
        $data = [
            'asdf' => [
                'key' => 2,
                'sub' => [
                    'key' => 2,
                ],
            ],
            'cde'  => [
                'key' => 1,
                'sub' => [
                    'key' => 3,
                ],
            ],
        ];

        return [
            [
                ['cde' => $data['cde'], 'asdf' => $data['asdf']],
                [$data, 'key'],
            ],
            [
                ['asdf' => $data['asdf'], 'cde' => $data['cde']],
                [$data, 'key', ['desc']],
            ],
            [
                ['asdf' => $data['asdf'], 'cde' => $data['cde']],
                [$data, 'sub.key'],
            ],
            [
                ['cde' => $data['cde'], 'asdf' => $data['asdf']],
                [$data, 'sub.key', ['desc']],
            ],
            [
                ['asdf' => $data['asdf'], 'cde' => $data['cde']],
                [$data, 'sub-key', ['separator' => '-']],
            ],
            [
                ['cde' => $data['cde'], 'asdf' => $data['asdf']],
                [$data, 'sub-key', ['desc', 'separator' => '-']],
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testSortByDataProvider
     */
    public function testSortBy($a, $b)
    {
        self::assertEquals($a, Arrays::sortBy(...$b));
    }

    public function _testWithoutDataProvider()
    {
        return [
            [
                ['foo' => true],
                [['true' => true, 'foo' => true], ['true']],
            ],
            [
                ['foo' => 123, 'bar' => 123],
                [['true' => true, 'foo' => 123, 'bar' => 123, 'false' => false], ['true', 'false']],
            ],
            [
                [['foo' => 'foo'], ['foo' => 'foo']],
                [[['foo' => 'foo', 'bar' => 'bar'], ['foo' => 'foo', 'bar' => 'bar']], ['*.bar']],
            ],
            [
                [['foo' => 'foo'], ['foo' => 'foo']],
                [[['foo' => 'foo', 'bar' => 'bar'], ['foo' => 'foo', 'bar' => 'bar']], [['*', 'bar']]],
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testWithoutDataProvider
     */
    public function testWithout($a, $b)
    {
        self::assertEquals($a, Arrays::without(...$b));
    }

    public function _testFlattenDataProvider()
    {
        return [
            [
                [
                    'foo'     => 123,
                    'bar.baz' => 234,
                ],
                [['foo' => 123, 'bar' => ['baz' => 234]]],
            ],
            [
                [
                    'foo'         => 123,
                    'bar.baz.bar' => 234,
                    'baz.bar.foo' => true,
                ],
                [['foo' => 123, 'bar' => ['baz' => ['bar' => 234]], 'baz' => ['bar' => ['foo' => true]]]],
            ],
            [
                [
                    'foo'     => 123,
                    'bar-baz' => 234,
                ],
                [['foo' => 123, 'bar' => ['baz' => 234]], ['separator' => '-']],
            ],
            [
                [
                    'foo'      => 123,
                    'bar.foo'  => 'bar',
                    'bar.true' => true,
                ],
                [['foo' => 123, 'bar' => new DummyIterator()]],
            ],
            [
                [
                    'foo' => 123,
                    'bar' => new DummyIterator(),
                ],
                [['foo' => 123, 'bar' => new DummyIterator()], ['arraysOnly']],
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testFlattenDataProvider
     */
    public function testFlatten($a, $b)
    {
        self::assertEquals($a, Arrays::flatten(...$b));
    }

    public function _testUnflattenDataProvider()
    {
        return [
            [
                ['foo' => 123, 'bar' => ['baz' => 234]],
                [['foo' => 123, 'bar.baz' => 234,]],
            ],
            [
                ['foo' => 123, 'bar' => ['baz' => ['bar' => 234]], 'baz' => ['bar' => ['foo' => true]]],
                [['foo' => 123, 'bar.baz.bar' => 234, 'baz.bar.foo' => true,]],
            ],
            [
                ['foo' => 123, 'bar' => ['baz' => 234]],
                [['foo' => 123, 'bar-baz' => 234,], ['separator' => '-']],
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testUnflattenDataProvider
     */
    public function testUnflatten($a, $b)
    {
        self::assertEquals($a, Arrays::unflatten(...$b));
    }

    public function testMapRecursive()
    {
        $list = [
            'foo' => 'bar',
            'bar' => [
                'baz' => [
                    'foo' => 123,
                    'bar' => 'bar',
                    'faz' => [
                        'bar' => true,
                        'baz' => false,
                    ],
                ],
            ],
        ];

        $expectValues = [
            // Value, Key, Path
            ['bar', 'foo', ['foo']],
            [123, 'foo', ['bar', 'baz', 'foo']],
            ['bar', 'bar', ['bar', 'baz', 'bar']],
            [true, 'bar', ['bar', 'baz', 'faz', 'bar']],
            [false, 'baz', ['bar', 'baz', 'faz', 'baz']],
        ];

        $c        = 0;
        $result   = Arrays::mapRecursive($list, function ($value, $key, $path) use (&$c, $expectValues) {
            $this->assertEquals($expectValues[$c++], [$value, $key, $path]);

            return 1;
        });
        $expected = [
            'foo' => 1,
            'bar' => [
                'baz' => [
                    'foo' => 1,
                    'bar' => 1,
                    'faz' => [
                        'bar' => 1,
                        'baz' => 1,
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $result);
    }

}
