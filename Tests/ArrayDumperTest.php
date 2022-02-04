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


namespace Neunerlei\Arrays\Tests\Assets;


use JsonException;
use Neunerlei\Arrays\Arrays;
use PHPUnit\Framework\TestCase;

class ArrayDumperTest extends TestCase
{
    
    public function _testToJsonDataProvider(): array
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
    public function testToJson($a, $b): void
    {
        static::assertEquals(Arrays::dumpToJson($a), $b);
        static::assertEquals(Arrays::dumpToJson($a, ['pretty' => false]), $b);
    }
    
    public function testToJsonFail(): void
    {
        $this->expectException(JsonException::class);
        
        $a = [123];
        $b = [&$a];
        $a[] = &$b;
        
        Arrays::dumpToJson($a);
    }
    
    public function _testToJsonPrettyProvider(): array
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
    public function testToJsonPretty($a, $b): void
    {
        static::assertEquals(Arrays::dumpToJson($a, ['pretty']), $b);
        static::assertEquals(Arrays::dumpToJson($a, ['pretty' => true]), $b);
    }
    
    public function testToJsonPrettyFail(): void
    {
        $this->expectException(JsonException::class);
        
        $a = [123];
        $b = [&$a];
        $a[] = &$b;
        
        Arrays::dumpToJson($a, ['pretty']);
    }
    
    public function testToJsonDepthLimitFail(): void
    {
        $this->expectException(JsonException::class);
        
        $a = [1, [2, [3, [4, [5, [6, [7, [8, [9]]]]]]]]];
        Arrays::dumpToJson($a, ['depth' => 2]);
    }
    
    public function testToJsonOptions(): void
    {
        $a = [1, [2, [3, [4, [5, [6, [7, [8, [9]]]]]]]]];
        static::assertEquals(
            '[1,[2,[3,[4,[5,[6,[7,[8,[9]]]]]]]]]',
            Arrays::dumpToJson($a, ['options' => JSON_PARTIAL_OUTPUT_ON_ERROR, 'depth' => 2]));
    }
}
