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
 * Last modified: 2021.01.30 at 16:10
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Tests\Assets;


use Neunerlei\Arrays\Arrays;
use PHPUnit\Framework\TestCase;

class ArrayDumperTest extends TestCase
{

    public function _testToJsonDataProvider()
    {
        return [
            [[], '[]'],
            [[123, 'foo', 'bar'], '[123,"foo","bar"]'],
            [['foo' => 'bar', 'bar' => 'baz'], '{"foo":"bar","bar":"baz"}'],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testToJsonDataProvider
     */
    public function testToJson($a, $b)
    {
        static::assertEquals(Arrays::dumpToJson($a), $b);
        static::assertEquals(Arrays::dumpToJson($a, ['pretty' => false]), $b);
    }

    public function testToJsonFail()
    {
        $this->expectException(\JsonException::class);

        $a   = [123];
        $b   = [&$a];
        $a[] = &$b;

        Arrays::dumpToJson($a);
    }

    public function _testToJsonPrettyProvider()
    {
        return [
            [[], '[]'],
            [
                [123, 'foo', 'bar'],
                '[
    123,
    "foo",
    "bar"
]',
            ],
            [
                ['foo' => 'bar', 'bar' => 'baz'],
                '{
    "foo": "bar",
    "bar": "baz"
}',
            ],
        ];
    }

    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testToJsonPrettyProvider
     */
    public function testToJsonPretty($a, $b)
    {
        static::assertEquals(Arrays::dumpToJson($a, ['pretty']), $b);
        static::assertEquals(Arrays::dumpToJson($a, ['pretty' => true]), $b);
    }

    public function testToJsonPrettyFail()
    {
        $this->expectException(\JsonException::class);

        $a   = [123];
        $b   = [&$a];
        $a[] = &$b;

        Arrays::dumpToJson($a, ['pretty']);
    }

    public function testToJsonDepthLimitFail()
    {
        $this->expectException(\JsonException::class);

        $a = [1, [2, [3, [4, [5, [6, [7, [8, [9]]]]]]]]];
        Arrays::dumpToJson($a, ['depth' => 2]);
    }

    public function testToJsonOptions()
    {
        $a = [1, [2, [3, [4, [5, [6, [7, [8, [9]]]]]]]]];
        static::assertEquals(
            Arrays::dumpToJson($a, ['options' => JSON_PARTIAL_OUTPUT_ON_ERROR, 'depth' => 2]),
            '[1,[2,[3,[4,[5,[6,[7,[8,[9]]]]]]]]]');
    }
}
