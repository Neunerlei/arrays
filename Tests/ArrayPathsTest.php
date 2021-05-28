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

use InvalidArgumentException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Arrays\EmptyPathException;
use Neunerlei\Arrays\Tests\Assets\FixtureArraysAdapter;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

class ArrayPathsTest extends TestCase
{

    public function _testParsePathDataProvider(): array
    {
        return [
            [['123'], '123'],
            [['123'], '123'],
            [['foo', 'bar', 'baz', '123'], ['foo', 'bar', 'baz', '123']],
            [['foo', 'bar', 'baz', '123'], ['foo', 'bar', 'baz', 123]],
            [['foo', 'bar', 'baz'], 'foo.bar.baz.'],
            [['foo', 'bar', 'baz'], 'foo.bar..baz'],
            [['foo', 'bar', 'baz'], 'foo-bar-baz', '-'],

            // Test if cached path is resolved correctly
            [['foo', 'bar', 'baz'], 'foo.bar.baz'],
            [['foo', 'bar', 'baz'], 'foo.bar.baz'],
            [['foo', 'bar', 'baz'], 'foo.bar.baz'],

            [['foo.bar', 'baz'], 'foo\\.bar.baz'],
            [['foo-bar', 'baz'], 'foo\\-bar-baz', '-'],
            [['[wild,wild2].*.horse.[carrot, orange]'], '\\[wild\\,wild2\\]\\.\\*\\.horse\\.\\[carrot\\, orange\\]'],
            [['foo', ['baz', '[bar]']], 'foo.[baz, \\[bar\\]]'],

            [['foo', ['bar', 'baz', 'foo']], 'foo.[bar,baz,foo]'],
            [['foo', ['bar', 'baz', ['foo', 'bar', 'baz']]], 'foo.[bar,baz,foo.bar.baz]'],
            [[['wild', 'wild2'], '*', 'horse', ['carrot', 'orange']], '[wild,wild2].*.horse.[carrot, orange]'],
        ];
    }

    /**
     * @param           $a
     * @param           $b
     * @param   null    $c
     * @param   bool    $d
     *
     * @dataProvider _testParsePathDataProvider
     */
    public function testParsePath($a, $b, $c = null): void
    {
        self::assertEquals($a, Arrays::parsePath($b, $c));
    }

    public function _testParsePathWithEmptyDataDataProvider(): array
    {
        return [
            ['', false],
            ['', true],
            [[], false],
            [[], true],
            ['.', false],
            ['.', true],
        ];
    }

    /**
     * @param $path
     * @param $allowEmpty
     *
     * @dataProvider _testParsePathWithEmptyDataDataProvider
     */
    public function testParsePathWithEmptyData($path, $allowEmpty): void
    {
        if ($allowEmpty) {
            self::assertEquals([], Arrays::parsePath($path, null, true));
        } else {
            $this->expectException(EmptyPathException::class);
            Arrays::parsePath($path);
        }
    }

    public function _testParsePathWithInvalidDataDataProvider(): array
    {
        return [
            [''],
            [[]],
            ['foo.[bar,baz,foo'],
            ['[[[[[[]]]]]]]]]]]]'],
            ['.'],
            ['foo.[bar,baz,foo.bar.baz, sub.[foo'],
        ];
    }

    /**
     * @dataProvider _testParsePathWithInvalidDataDataProvider
     *
     * @param $v
     */
    public function testParsePathWithInvalidData($v): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::parsePath($v);
    }

    public function _testParsePathWithInvalidTypeDataProvider(): array
    {
        return [
            [static function () { }],
            [['string', static function () { }]],
            [['string', 'int', 123, [['foo', static function () { }]]]],
        ];
    }

    /**
     * @dataProvider _testParsePathWithInvalidTypeDataProvider
     *
     * @param $v
     */
    public function testParsePathWithInvalidTypeData($v): void
    {
        $this->expectException(TypeError::class);
        Arrays::parsePath($v);
    }

    public function testPathCacheClearing(): void
    {
        FixtureArraysAdapter::clearPathCache();

        self::assertEquals([[], []], FixtureArraysAdapter::getPathCacheData());

        $paths = [
            'foo.1',
            'foo.2',
            'foo.3',
            'foo.4',
            'foo.5',
            'foo.6',
            'foo.7',
            'foo.8',
            'foo.9',
            'foo.10',
            'foo.11',
            'foo.12',
            'foo.13',
            'foo.14',
            'foo.15',
            'foo.16',
            'foo.17',
            'foo.18',
            'foo.19',
            'foo.20',
            'foo',
            'foo.bar.baz',
            'foo.bar',
            'foo',
            'bar',
            'baz.bar',
            'foo',
            'bar.baz',
        ];

        foreach ($paths as $path) {
            Arrays::parsePath($path);
        }

        $expect = [
            [
                '643abf9958f6c82862a725b2f492e017' => ['foo', '7',],
                'dec770feaa7688a16cca637b1cdcfa7d' => ['foo', '8',],
                'd22ff20bff3e9b38ca716b4fef313420' => ['foo', '9',],
                'ddd7eddf11037e558bf480bf174d31bf' => ['foo', '10',],
                'b97e6c26fc4f59dc161777b40465452b' => ['foo', '11',],
                'e76ceba24c7a9c41054c9553b8f5f3bd' => ['foo', '12',],
                'c2b99c70912e061eb1450d5cf41cbdcd' => ['foo', '13',],
                '3ab4221840c4a6165c71354f3698110f' => ['foo', '14',],
                'eb985e1bcf19056d5aef4841f5b2e123' => ['foo', '15',],
                '4bc4aa5dfc4717ee30bea56b8fc5f3f7' => ['foo', '16',],
                'abd8e37629053e73afabb9b90a54b2fb' => ['foo', '17',],
                '19e29c0948012148b5d73f8cd1c0ba8c' => ['foo', '18',],
                '7e8a0e7abb8157125e5d5e7e39d78d76' => ['foo', '19',],
                '78c31d65116c9c9d55eaa2716b9325f2' => ['foo', '20',],
                '0efa2e208a21d7f8ccac9cebfacc5bbc' => ['foo',],
                '1013b13d5463624811acf532ec5a64d2' => ['foo', 'bar', 'baz',],
                '11bb841afd4549a9377a90074f8833fc' => ['foo', 'bar',],
                'cbc5b55114d9e43c90d71606fd640972' => ['bar',],
                '6810837a1944088d1ce0f83e08a40b78' => ['baz', 'bar',],
                '6ca4b4cb959abe06d25dcd3c79314893' => ['bar', 'baz',],
            ],
            [
                '643abf9958f6c82862a725b2f492e017',
                'dec770feaa7688a16cca637b1cdcfa7d',
                'd22ff20bff3e9b38ca716b4fef313420',
                'ddd7eddf11037e558bf480bf174d31bf',
                'b97e6c26fc4f59dc161777b40465452b',
                'e76ceba24c7a9c41054c9553b8f5f3bd',
                'c2b99c70912e061eb1450d5cf41cbdcd',
                '3ab4221840c4a6165c71354f3698110f',
                'eb985e1bcf19056d5aef4841f5b2e123',
                '4bc4aa5dfc4717ee30bea56b8fc5f3f7',
                'abd8e37629053e73afabb9b90a54b2fb',
                '19e29c0948012148b5d73f8cd1c0ba8c',
                '7e8a0e7abb8157125e5d5e7e39d78d76',
                '78c31d65116c9c9d55eaa2716b9325f2',
                '1013b13d5463624811acf532ec5a64d2',
                '11bb841afd4549a9377a90074f8833fc',
                'cbc5b55114d9e43c90d71606fd640972',
                '6810837a1944088d1ce0f83e08a40b78',
                '0efa2e208a21d7f8ccac9cebfacc5bbc',
                '6ca4b4cb959abe06d25dcd3c79314893',
            ],
        ];

        static::assertEquals($expect, FixtureArraysAdapter::getPathCacheData());
    }

    public function _testMergePathsDataProvider(): array
    {
        return [
            [['foo', 'bar', 'bar', 'baz'], 'foo.bar', 'bar.baz'],
            [['foo', 'bar', 'bar', 'baz'], 'foo.bar.', 'bar.baz'],
            [['foo', 'bar', 'bar', 'baz'], 'foo,bar', 'bar,baz', ','],
            [['foo', 'bar', 'bar', 'baz'], 'foo,bar', 'bar-baz', ',', '-'],
            [['foo', 'bar', 'bar', 'baz'], 'foo.bar', ['bar', 'baz']],
            [['foo', 'bar', 'bar', 'baz'], ['foo', 'bar'], 'bar.baz'],
            [['foo', 'bar', 'bar', 'baz'], ['foo', 'bar'], ['bar', 'baz']],
        ];
    }

    /**
     * @param           $a
     * @param           $b
     * @param           $c
     * @param   null    $d
     * @param   null    $e
     *
     * @dataProvider _testMergePathsDataProvider
     */
    public function testMergePaths($a, $b, $c, $d = null, $e = null): void
    {
        self::assertEquals($a, Arrays::mergePaths($b, $c, $d, $e));
    }

    public function provideTestCanUseFastLaneData(): array
    {
        return [
            ['a', '.', true],
            [['a', 'b'], '.', false],
            [[], '.', false],
            ['a[foo]', '.', false],
            ['asdf_jklö', '.', true],
            ['a.b', '.', false],
            ['a_b', '_', false],
            ['a.b', '_', true],
        ];
    }

    /**
     * @param           $path
     * @param   string  $separator
     * @param   bool    $expect
     *
     * @dataProvider provideTestCanUseFastLaneData
     */
    public function testCanUseFastLane($path, string $separator, bool $expect): void
    {
        self::assertEquals($expect,
            FixtureArraysAdapter::callMethod('canUseFastLane', $path, $separator)
        );
    }

    public function testInitWalkerStep(): void
    {
        self::assertEquals([
            ['rumpel'],
            false,
            0 // ArrayPaths::KEY_TYPE_DEFAULT,
        ],
            FixtureArraysAdapter::callMethod('initWalkerStep', $this->getTree(), ['rumpel', 'pumpel']));

        self::assertEquals([
            ['foo', 'bar', 'baz', 'rumpel', 'wild', 'wild2'],
            false,
            1 // ArrayPaths::KEY_TYPE_WILDCARD,
        ],
            FixtureArraysAdapter::callMethod('initWalkerStep', $this->getTree(), ['*', 'pumpel']));

        self::assertEquals([
            ['baz', ['rumpel', 'grumpel'], ['rumpel', 'foo']],
            false,
            2 //ArrayPaths::KEY_TYPE_KEYS,
        ],
            FixtureArraysAdapter::callMethod('initWalkerStep', $this->getTree(),
                [['baz', ['rumpel', 'grumpel'], ['rumpel', 'foo']], 'pumpel']));
    }

    /**
     * HAS PATH
     */
    public function testHasSimplePath(): void
    {
        self::assertTrue(Arrays::hasPath($this->getTree(), 'baz'));
        self::assertFalse(Arrays::hasPath($this->getTree(), 'fooBar'));
        self::assertTrue(Arrays::hasPath($this->getTree(), 'baz.2.1'));
        self::assertTrue(Arrays::hasPath($this->getTree(), 'rumpel.pumpel.foo'));
        self::assertFalse(Arrays::hasPath($this->getTree(), 'rumpel.pumpel.foo2'));
        self::assertTrue(Arrays::hasPath($this->getTree(), ['rumpel', 'pumpel', 'foo']));
        self::assertFalse(Arrays::hasPath($this->getTree(), ['rumpel', 'pumpel', 'foo3']));
    }

    public function testHasWildcardPath(): void
    {
        self::assertTrue(Arrays::hasPath($this->getTree(), 'wild.*.foo'));
        self::assertFalse(Arrays::hasPath($this->getTree(), 'rumpel.*.foo'));
        self::assertTrue(Arrays::hasPath($this->getTree(), ['rumpel', 'pumpel', 'foo']));
        self::assertTrue(Arrays::hasPath($this->getTree(), 'wild.*.horse.carrot'));
        self::assertTrue(Arrays::hasPath($this->getTree(), ['wild', '*', 'horse', 'carrot']));
    }

    public function testHasPathWithSubKeys(): void
    {
        self::assertTrue(Arrays::hasPath($this->getTree(), '[foo,bar]'));
        self::assertTrue(Arrays::hasPath($this->getTree(), [['foo', 'bar']]));
        self::assertFalse(Arrays::hasPath($this->getTree(), [['foo', 'bar', 'foz']]));
        self::assertTrue(Arrays::hasPath($this->getTree(), ['rumpel', ['pumpel', 'grumpel']]));
        self::assertTrue(Arrays::hasPath($this->getTree(), ['rumpel', [['pumpel', 'foo'], 'grumpel']]));
        self::assertTrue(Arrays::hasPath($this->getTree(), 'wild.*.horse.[carrot,saddle]'));
        self::assertFalse(Arrays::hasPath($this->getTree(), 'wild.*.horse.[carrot,saddle,rose]'));
        self::assertTrue(Arrays::hasPath($this->getTree(), '[wild,wild2].*.foo'));

        self::assertFalse(Arrays::hasPath([], 'wild.*.horse.[carrot,saddle,rose]'));
    }

    public function testHasFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::hasPath(['asdf' => 'foo'], null);
    }

    /**
     * GET PATH
     */
    public function testGetSimplePath(): void
    {
        self::assertEquals('bar', Arrays::getPath($this->getTree(), 'baz.2.1'));
        self::assertEquals('pumpFoo', Arrays::getPath($this->getTree(), 'rumpel.pumpel.foo'));
        self::assertNull(Arrays::getPath($this->getTree(), 'rumpel.pumpel.foo2'));
        self::assertFalse(Arrays::getPath($this->getTree(), 'rumpel.pumpel.foo2', false));
        self::assertEquals('pumpFoo', Arrays::getPath($this->getTree(), ['rumpel', 'pumpel', 'foo']));
        self::assertNull(Arrays::getPath($this->getTree(), ['rumpel', 'pumpel', 'foo3']));
        self::assertNull(Arrays::getPath(['a' => ['b' => []]], 'a.b.c.d'));
    }

    public function testGetWildcardPath(): void
    {
        self::assertEquals([123, 234], Arrays::getPath($this->getTree(), 'wild.*.foo'));
        self::assertEquals(['pumpel' => 'pumpFoo', 'foo' => 1, 'grumpel' => 1],
            Arrays::getPath($this->getTree(), 'rumpel.*.foo', 1));
        self::assertEquals('pumpFoo', Arrays::getPath($this->getTree(), ['rumpel', 'pumpel', 'foo']));
        self::assertEquals([123, 562], Arrays::getPath($this->getTree(), 'wild.*.horse.carrot'));
        self::assertEquals([
            [
                'carrot' => 123,
                'stick'  => 234,
                'saddle' => 345,
            ],
            [
                'carrot' => 562,
                'stick'  => 678,
                'saddle' => 903,
            ],
        ], Arrays::getPath($this->getTree(), 'wild.*.horse.*'));
    }

    public function testGetWithSubKeys(): void
    {
        self::assertNull(Arrays::getPath([], 'foo'));
        self::assertEquals(['bar' => 123, 'foo' => 'bar'], Arrays::getPath($this->getTree(), '[foo,bar]'));
        self::assertEquals(['bar' => 123, 'foo' => 'bar'], Arrays::getPath($this->getTree(), [['foo', 'bar']]));
        self::assertEquals(
            ['bar' => 123, 'foo' => 'bar', 'foz' => null],
            Arrays::getPath($this->getTree(), [['foo', 'bar', 'foz']]));

        // Those should all lead to the same result
        $r = [
            'grumpel' => 555,
            'pumpel'  => [
                'foo' => 'pumpFoo',
                'bar' => 'pumpBar',
            ],
        ];
        self::assertEquals($r, Arrays::getPath($this->getTree(), ['rumpel', ['pumpel', 'grumpel']]));
        self::assertEquals($r, Arrays::getPath($this->getTree(), 'rumpel.[grumpel,pumpel.foo,pumpel.bar]'));
        self::assertEquals($r, Arrays::getPath($this->getTree(), 'rumpel.[grumpel,pumpel.*]'));
        self::assertEquals($r, Arrays::getPath($this->getTree(), 'rumpel.[grumpel,pumpel.[foo,bar]]'));

        self::assertEquals(
            ['grumpel' => 555, 'pumpel' => ['foo' => 'pumpFoo']],
            Arrays::getPath($this->getTree(), ['rumpel', [['pumpel', 'foo'], 'grumpel']]));

        self::assertEquals(
            [
                ['carrot' => 123, 'saddle' => 345],
                ['carrot' => 562, 'saddle' => 903],
            ],
            Arrays::getPath($this->getTree(), 'wild.*.horse.[carrot,saddle]'));

        self::assertEquals(
            [
                ['carrot' => 123, 'rose' => null, 'saddle' => 345],
                ['carrot' => 562, 'rose' => null, 'saddle' => 903],
            ],
            Arrays::getPath($this->getTree(), 'wild.*.horse.[carrot,rose,saddle]'));

        self::assertEquals([
            'wild'  => [123, 234],
            'wild2' => ['asdf', 'bar', 'baz'],
        ], Arrays::getPath($this->getTree(), '[wild,wild2].*.foo'));
    }

    public function testGetFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::getPath(['asdf' => 'foo'], null);
    }

    /**
     * SET PATH
     */
    public function testSetSimplePath(): void
    {
        self::assertEquals(['foo' => 'bar'], Arrays::setPath([], 'foo', 'bar'));
        self::assertEquals(['foo' => 'bar', 'bar' => 'baz'], Arrays::setPath(['bar' => 'baz'], 'foo', 'bar'));

        $r                      = [
            'foo' => [
                'bar' => 'baz',
                'baz' => [
                    'bar' => 'foo',
                ],
            ],
        ];
        $e                      = $r;
        $e['foo']['baz']['foo'] = true;
        self::assertEquals($e, Arrays::setPath($r, 'foo.baz.foo', true));
        self::assertEquals($e, Arrays::setPath($r, ['foo', 'baz', 'foo'], true));
    }

    public function testSetWildcardPath(): void
    {
        $r           = [
            [
                'foo' => 123,
            ],
            [
                'foo' => 123,
            ],
        ];
        $e           = $r;
        $e[0]['bar'] = true;
        $e[1]['bar'] = true;
        self::assertEquals($e, Arrays::setPath($r, '*.bar', true));
        self::assertEquals($e, Arrays::setPath($r, ['*', 'bar'], true));

        $r                         = [
            'key' => true,
            'sub' => [
                [
                    'foo' => 123,
                    'bar' => [],
                ],
                [
                    'foo' => 123,
                    'bar' => [],
                ],
            ],
        ];
        $e                         = $r;
        $e['sub'][0]['bar']['key'] = 123;
        $e['sub'][1]['bar']['key'] = 123;
        self::assertEquals($e, Arrays::setPath($r, 'sub.*.bar.key', 123));
        self::assertEquals($e, Arrays::setPath($r, ['sub', '*', 'bar', 'key'], 123));

        $r    = [
            [
                'foo' => 123,
            ],
            [
                'foo' => 123,
            ],
        ];
        $e    = $r;
        $e[0] = false;
        $e[1] = false;
        self::assertEquals($e, Arrays::setPath($r, '*', false));
        self::assertEquals($e, Arrays::setPath($r, ['*'], false));
    }

    public function testSetPathWithSubKeys(): void
    {
        $r                               = $this->getTree();
        $e                               = $r;
        $e['wild'][0]['horse']['carrot'] = 'foo';
        $e['wild'][0]['horse']['saddle'] = 'foo';
        $e['wild'][0]['horse']['free']   = 'foo';
        $e['wild'][1]['horse']['carrot'] = 'foo';
        $e['wild'][1]['horse']['saddle'] = 'foo';
        $e['wild'][1]['horse']['free']   = 'foo';
        self::assertEquals($e, Arrays::setPath($r, 'wild.*.horse.[carrot,saddle,free]', 'foo'));
        self::assertEquals($e, Arrays::setPath($r, ['wild', '*', 'horse', ['carrot', 'saddle', 'free']], 'foo'));

        $r                            = $this->getTree();
        $e                            = $r;
        $e['baz']['pumpel']['bar']    = 123;
        $e['rumpel']['pumpel']['bar'] = 123;
        self::assertEquals($e, Arrays::setPath($r, '[baz,rumpel].pumpel.bar', 123));

        $r                                = $this->getTree();
        $e                                = $r;
        $e['wild'][0]['horse']['carrot']  = 'orange';
        $e['wild'][1]['horse']['carrot']  = 'orange';
        $e['wild'][0]['horse']['orange']  = 'orange';
        $e['wild'][1]['horse']['orange']  = 'orange';
        $e['wild2'][0]['horse']['carrot'] = 'orange';
        $e['wild2'][0]['horse']['orange'] = 'orange';
        $e['wild2'][1]['horse']['carrot'] = 'orange';
        $e['wild2'][1]['horse']['orange'] = 'orange';
        $e['wild2'][2]['horse']['carrot'] = 'orange';
        $e['wild2'][2]['horse']['orange'] = 'orange';
        self::assertEquals($e, Arrays::setPath($r, '[wild,wild2].*.horse.[carrot, orange]', 'orange'));

        $r                             = $this->getTree();
        $e                             = $r;
        $e['baz']['foo']['bar']['baz'] = true;
        $e['baz']['foo']['bar']['bar'] = true;
        self::assertEquals($e, Arrays::setPath($r, 'baz.foo.[bar.baz, bar.bar]', true));
    }


    public function testSetFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::setPath([], null, true);
    }

    /**
     * REMOVE PATH
     */
    public function testRemoveSimplePath(): void
    {
        self::assertEquals([], Arrays::removePath(['foo' => 'bar'], 'foo'));
        self::assertEquals([], Arrays::removePath(['foo' => ['bar' => 'baz']], 'foo'));
        self::assertEquals([], Arrays::removePath(['foo' => ['bar' => 'baz']], 'foo.bar'));
        self::assertEquals(['foo' => []], Arrays::removePath(['foo' => ['bar' => 'baz']], 'foo.bar', ['keepEmpty']));

        $r = $this->getTree();
        $e = $r;
        unset($e['baz'][2][1]);
        self::assertEquals($e, Arrays::removePath($r, 'baz.2.1'));
    }

    public function testRemoveWildcardPath(): void
    {
        self::assertEquals([], Arrays::removePath(['foo' => 'bar'], '*'));
        self::assertEquals([], Arrays::removePath($this->getTree(), '*'));

        $r = $this->getTree();
        $e = $r;
        unset($e['baz'][2][0]);
        self::assertEquals($e, Arrays::removePath($r, '*.*.0'));

        $r = $this->getTree();
        $e = $r;
        unset($e['wild2'], $e['wild'][0]['foo'], $e['wild'][1]['foo']);
        self::assertEquals($e, Arrays::removePath($r, '[wild,wild2].*.foo'));

        unset($e['rumpel']['pumpel']['foo']);
        self::assertEquals($e, Arrays::removePath($r, '*.*.foo'));
    }

    public function testRemoveWithSubKeys(): void
    {
        $r = $this->getTree();
        $e = $r;
        unset($e['foo'], $e['baz'][2]);
        self::assertEquals($e, Arrays::removePath($r, '[foo,baz.2]'));

        $r = $this->getTree();
        $e = $r;
        unset($e['foo'], $e['baz']);
        self::assertEquals($e, Arrays::removePath($r, '[foo,baz.1,baz.2,baz.0]'));

        $r = $this->getTree();
        $e = $r;
        unset($e['rumpel']['pumpel']);
        self::assertEquals($e, Arrays::removePath($r, 'rumpel.pumpel.[foo,bar]'));
        self::assertEquals($e, Arrays::removePath($r, 'rumpel.pumpel.*'));
        self::assertEquals($e, Arrays::removePath($r, 'rumpel.pumpel'));
    }

    public function testRemoveFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::removePath([], null);
    }

    /**
     * FILTER PATH
     */
    public function testFilter(): void
    {
        Arrays::filterPath([], '*.foo', static function () {
            self::fail('An empty array should never call the filter callback');
        });

        $list         = $this->getTree();
        $r            = Arrays::filterPath($this->getTree(), 'wild.*',
            static function ($v, $key, $path, $initialList) use ($list) {
                if ($path !== ['wild', 0] && $path !== ['wild', 1]) {
                    self::fail('Filter path was not as expected!');
                }
                self::assertEquals($list, $initialList);
                self::assertIsArray($v);
                self::assertArrayHasKey('foo', $v);

                return 'asdf';
            });
        $e            = $this->getTree();
        $e['wild'][0] = 'asdf';
        $e['wild'][1] = 'asdf';
        self::assertEquals($e, $r);

        $r                            = Arrays::filterPath($this->getTree(), 'rumpel.[pumpel.foo,grumpel]',
            static function ($v) {
                if ($v !== 555 && $v !== 'pumpFoo') {
                    self::fail('Filter value was not as expected!');
                }

                return true;
            });
        $e                            = $this->getTree();
        $e['rumpel']['pumpel']['foo'] = true;
        $e['rumpel']['grumpel']       = true;
        self::assertEquals($e, $r);
    }

    public function testFilterFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Arrays::filterPath([], null, static function () { });
    }

    /**
     * GET LIST
     */
    public function testGetListExamples(): void
    {
        // Example 1: Return a list of all "id" values
        self::assertEquals(['234', '123'], Arrays::getList($this->getList(), 'id'));
        self::assertEquals(['234', '123'], Arrays::getList($this->getList(), ['id']));

        // Example 2: Return a list of all "id" and "title" values
        self::assertEquals(
            [
                ['id' => '234', 'title' => 'medium'],
                ['id' => '123', 'title' => 'apple'],
            ],
            Arrays::getList($this->getList(), ['id', 'title']));

        // Example 3: Return a list of all "title" values with their "id" as key
        self::assertEquals(['234' => 'medium', '123' => 'apple'], Arrays::getList($this->getList(), 'title', 'id'));

        // Example 4: Path lookup and aliases for longer keys
        self::assertEquals(
            [
                '234' => ['array.id' => '12', 'myAlias' => 'di'],
                '123' => ['array.id' => '23', 'myAlias' => 'pumpel'],
            ], Arrays::getList($this->getList(), ['array.id', 'array.rumpel as myAlias'], 'id'));

        // Example 5: Path lookup and default value for unknown keys
        self::assertEquals(
            [
                '234' => ['array.id' => '12', 'array.bar' => 'baz'],
                '123' => ['array.id' => '23', 'array.bar' => null],
            ], Arrays::getList($this->getList(), ['array.id', 'array.bar'], 'id'));

        self::assertEquals(
            [
                '234' => ['array.id' => '12', 'array.bar' => 'baz', 'baz' => true, 'baz2' => true],
                '123' => ['array.id' => '23', 'array.bar' => true, 'baz' => true, 'baz2' => true],
            ], Arrays::getList($this->getList(), ['array.id', 'array.bar', 'baz', 'array.baz as baz2'], 'id',
            ['default' => true]));

        // Example 6: Keep the rows identical but use a column value as key in the result array
        $r = ['234' => $this->getList()[0], '123' => $this->getList()[1]];
        self::assertEquals($r, Arrays::getList($this->getList(), null, 'id'));

        // Example 7: Dealing with path based key lookups
        $r = ['12' => '234', '23' => '123'];
        self::assertEquals($r, Arrays::getList($this->getList(), 'id', 'array.id'));
    }

    public function provideTestGetListEdgeCasesData(): array
    {
        return [
            [
                [[], ['foo', 'bar']],
                [],
            ],
            [
                [['a' => 'b', 'b' => 'c'], []],
                ['a' => 'b', 'b' => 'c'],
            ],
            [
                [['a' => 'b', 'b' => 'c'], null],
                ['a' => 'b', 'b' => 'c'],
            ],
            [
                [['a' => 'b', 'b' => 'c'], ['*']],
                ['a' => 'b', 'b' => 'c'],
            ],
            [
                // This is probably a misconfiguration but should work in this case
                [[['a' => ['b' => 1]], ['a' => ['b' => 2]], ['a' => ['b' => 3]]], '*.b'],
                [['a' => 1], ['a' => 2], ['a' => 3]],
            ],
            [
                // This can't work because non array children are simply ignored
                [['a' => 'b', 'b' => 'c'], ['d']],
                [],
            ],
            [
                [['a' => ['b' => 1], 'b' => ['c' => 2]], ['d']],
                [null, null],
            ],
            // This option tests how normal fields aliases are handled
            [
                [
                    [
                        ['foo' => 1, 'bar' => 'a'],
                        ['foo' => 2, 'bar' => 'a'],
                        ['foo' => 3, 'bar' => 'a'],
                    ],
                    [
                        'foo as bar',
                    ],
                ],
                [
                    ['bar' => 1],
                    ['bar' => 2],
                    ['bar' => 3],
                ],
            ],
            // Tests the behaviour if $keyKey is set to true meaning "keep the original key"
            [
                [
                    [
                        'foo' => [
                            'bar' => 1,
                        ],
                        'baz' => [
                            'bar' => 2,
                        ],
                    ],
                    ['bar'],
                    true,
                ],
                ['foo' => 1, 'baz' => 2],
            ],
            // Tests the behaviour if $keyKey is set to true with an empty $valueKeys array
            // This results in the input array to be returned
            [
                [
                    [
                        'foo' => [
                            'bar' => 1,
                        ],
                        'baz' => [
                            'bar' => 2,
                        ],
                    ],
                    [],
                    true,
                ],
                [
                    'foo' => [
                        'bar' => 1,
                    ],
                    'baz' => [
                        'bar' => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param   array       $args
     * @param   array|null  $expect
     *
     * @dataProvider provideTestGetListEdgeCasesData
     */
    public function testGetListEdgeCases(array $args, ?array $expect): void
    {
        self::assertEquals($expect, Arrays::getList(...$args));
    }

    public function testGetListInvalidValueKeys(): void
    {
        $this->expectException(TypeError::class);
        Arrays::getList([[]], new stdClass());
    }

    protected function getList(): array
    {
        return [
            [
                'id'    => '234',
                'title' => 'medium',
                'asdf'  => 'asdf',
                'array' => [
                    'id'     => '12',
                    'rumpel' => 'di',
                    'bar'    => 'baz',
                ],
            ],
            [
                'id'    => '123',
                'title' => 'apple',
                'asdf'  => 'asdf',
                'array' => [
                    'id'     => '23',
                    'rumpel' => 'pumpel',
                    'foo'    => 'bar',
                ],
            ],
        ];
    }

    protected function getTree(): array
    {
        return [
            'foo'    => 'bar',
            'bar'    => 123,
            'baz'    => [
                123,
                234,
                [
                    'foo',
                    'bar',
                ],
            ],
            'rumpel' => [
                'pumpel'  => [
                    'foo' => 'pumpFoo',
                    'bar' => 'pumpBar',
                ],
                'grumpel' => 555,
                'foo'     => 222,
            ],
            'wild'   => [
                [
                    'foo'   => 123,
                    'horse' => [
                        'carrot' => 123,
                        'stick'  => 234,
                        'saddle' => 345,
                    ],
                ],
                [
                    'foo'   => 234,
                    'horse' => [
                        'carrot' => 562,
                        'stick'  => 678,
                        'saddle' => 903,
                    ],
                ],
            ],
            'wild2'  => [
                ['foo' => 'asdf'],
                ['foo' => 'bar'],
                ['foo' => 'baz'],
            ],
        ];
    }
}
