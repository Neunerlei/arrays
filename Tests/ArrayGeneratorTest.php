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


use Neunerlei\Arrays\ArrayGeneratorException;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Arrays\Features\Generator\FromObjectGenerator;
use Neunerlei\Arrays\Features\Generator\FromXmlGenerator;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use stdClass;

class ArrayGeneratorTest extends TestCase
{
    
    public function _testFromXmlDataProvider(): array
    {
        $xml
            = '<body><node><subNode>Hello</subNode><otherSubNode>123</otherSubNode></node><node><subNode>Hello, from me too!</subNode></node></body>';
        $xmlExpected = [
            [
                'tag' => 'body',
                [
                    'tag' => 'node',
                    [
                        'tag' => 'subNode',
                        'content' => 'Hello',
                    ],
                    [
                        'tag' => 'otherSubNode',
                        'content' => '123',
                    ],
                ],
                [
                    'tag' => 'node',
                    [
                        'tag' => 'subNode',
                        'content' => 'Hello, from me too!',
                    ],
                ],
            ],
        ];
        
        return [
            [['foo' => 123], ['foo' => 123]],
            [$xmlExpected, '<?xml version="1.0" encoding="UTF-8"?>' . $xml],
            [$xmlExpected, $xml],
            [$xmlExpected, new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . $xml)],
            [
                $xmlExpected,
                dom_import_simplexml(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' . $xml)),
            ],
            [
                [
                    [
                        'tag' => 'body',
                        [
                            'tag' => 'node',
                            '@data-my-attr' => 'foo-bar',
                            'content' => 'Baz',
                        ],
                    ],
                ],
                '<body><node data-my-attr="foo-bar">Baz</node></body>',
            ],
            [[], ''],
            [[], null],
            [
                [
                    [
                        'tag' => 'h:table',
                        [
                            'tag' => 'h:tr',
                            'content' => 'bar',
                            '@h:foo' => 'bar',
                        ],
                    ],
                ],
                '<?xml version="1.0" encoding="UTF-8" ?><h:table xmlns:h="http://www.w3.org/TR/html4/"><h:tr h:foo="bar">bar</h:tr></h:table>',
            ],
        ];
    }
    
    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testFromXmlDataProvider
     */
    public function testFromXml($a, $b): void
    {
        self::assertEquals($a, Arrays::makeFromXml($b));
    }
    
    public function _testFromXmlFailDataProvider(): array
    {
        return [
            ['asdf'],
            [new stdClass()],
            [123],
            ['<body><tag><tag2></tag>'],
        ];
    }
    
    /**
     * @dataProvider _testFromXmlFailDataProvider
     *
     * @param $v
     */
    public function testFromXmlFail($v): void
    {
        $this->expectException(ArrayGeneratorException::class);
        Arrays::makeFromXml($v);
    }
    
    public function testFromXmlAsAssoc(): void
    {
        $xml
            = '<body><node><subNode>Hello</subNode><otherSubNode>123</otherSubNode></node><node><subNode>Hello, from me too!</subNode></node></body>';
        self::assertEquals([
            'body' => [
                'node' => [
                    'subNode' => 'Hello, from me too!',
                ],
            ],
        ], Arrays::makeFromXml($xml, true));
        $xml
            = '<body><foo faz="baz"><subNode foo="bar">Hello</subNode><otherSubNode>123</otherSubNode></foo><bar><subNode>Hello, from me too!</subNode></bar></body>';
        self::assertEquals([
            'body' => [
                'foo' => [
                    'subNode' => 'Hello',
                    'otherSubNode' => '123',
                ],
                'bar' => [
                    'subNode' => 'Hello, from me too!',
                ],
            ],
        ], Arrays::makeFromXml($xml, true));
    }
    
    public function _testFromObjectDataProvider(): array
    {
        return [
            [['foo' => true], ['foo' => true]],
            [[], ''],
            [[], null],
            [['foo' => 'bar', 'bar' => true], (object)['foo' => 'bar', 'bar' => true]],
            [
                [
                    [
                        'tag' => 'body',
                        'content' => 'foo!',
                    ],
                ],
                new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><body>foo!</body>'),
            ],
            [
                [
                    [
                        'tag' => 'body',
                        'content' => 'foo!',
                    ],
                ],
                dom_import_simplexml(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><body>foo!</body>')),
            ],
            [['foo' => 'bar', 'true' => true], new DummyIterator()],
            [['foo' => true, 'bar' => 'baz'], new DummyClass()],
        ];
    }
    
    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testFromObjectDataProvider
     */
    public function testFromObject($a, $b): void
    {
        self::assertEquals($a, Arrays::makeFromObject($b));
    }
    
    public function testFromObjectWithCustomXmlGenerator(): void
    {
        $xmlGenerator = new class extends FromXmlGenerator {
            public $executed = false;
            
            public function generate($input, bool $asAssocArray = false): array
            {
                $this->executed = true;
                
                return parent::generate($input, $asAssocArray);
            }
        };
        
        $generator = new FromObjectGenerator();
        $generator->setXmlGenerator($xmlGenerator);
        
        $expect = [
            [
                'tag' => 'body',
                'content' => 'foo!',
            ],
        ];
        $input = dom_import_simplexml(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><body>foo!</body>'));
        
        self::assertEquals($expect, $generator->generate($input));
    }
    
    public function testFromObjectWithNoObject(): void
    {
        $this->expectException(ArrayGeneratorException::class);
        Arrays::makeFromObject('asdf');
    }
    
    public function _testFromStringListDataProvider(): array
    {
        return [
            [[], []],
            [[], ''],
            [[], null],
            [[0], '0'],
            [['0'], '0', ['convertTypes' => false]],
            [[0], 0],
            [[123.23], 123.23],
            [[123.23], '123.23'],
            [['foo', 'bar', 'baz'], new DummyToString()],
            [['foo', 'bar', 'baz'], 'foo , bar,    baz'],
            [[true, false, true, 1, 0, null, 123], 'true,FALSE,TRUE, 1, , 0,  NULL,, 123  '],
            [[true, false, true, 1, 0, null, 123], 'true,false,true, 1, , 0,  null,, 123  '],
            [[true, false, true, 1, 0, null, 123], 'true,false,true, 1, , 0,  null,, 123  ', ['convertTypes' => true]],
            [
                ['true', 'FALSE', 'true', '1', '0', 'null', '123'],
                'true,FALSE,true, 1, , 0,  null,, 123  ',
                ['convertTypes' => false],
            ],
            [['foo,bar'], 'foo\\,bar'],
            [['foo,bar'], 'foo\\,bar', ['convertTypes' => false]],
            [['foo', 'bar', 'baz'], 'foo-bar-baz', '-'],
            [['foo', 'bar', 'baz'], 'foo-bar-baz', ['separator' => '-']],
            [['fooTrue', 'fooNull'], 'fooTrue,fooNull'],
            [[-123.23], '-123.23'],
            [[-123.23], '-123.23', ['strictNumerics' => false]],
            [['-123.23'], '-123.23', ['strictNumerics']],
            [['-123.23'], '-123.23', ['strictNumerics' => true]],
            [[-1, 1231281231, '213123-234', 123], '-1,+1231281231,213123-234,123'],
            [
                ['-1', '+1231281231', '213123-234', 123, 123.12],
                '-1,+1231281231,213123-234,123,123.12',
                ['strictNumerics'],
            ],
        ];
    }
    
    /**
     * @param           $a
     * @param           $b
     * @param   string  $sep
     *
     * @dataProvider _testFromStringListDataProvider
     */
    public function testFromStringList($a, $b, $sep = null, $options = null): void
    {
        self::assertEquals($a, Arrays::makeFromStringList($b, $sep, $options));
    }
    
    
    public function _testFromStringListFailDataProvider(): array
    {
        return [
            [new stdClass()],
            [new DummyClass()],
            [new DummyIterator()],
        ];
    }
    
    /**
     * @param $v
     *
     * @dataProvider _testFromStringListFailDataProvider
     */
    public function testFromStringListFail($v): void
    {
        $this->expectException(ArrayGeneratorException::class);
        Arrays::makeFromStringList($v);
    }
    
    public function _testFromCsvDataProvider(): array
    {
        return [
            [[], []],
            [[], ''],
            [[], null],
            [[['a', 'b', 'c']], 'a,b,c'],
            [[['a', 'b', 'c'], ['d', 'e', 'f']], 'a,b,c' . PHP_EOL . 'd,e,f'],
            [[['a', 'b', 'c'], ['d', 'e', 'f']], 'a, b, c' . PHP_EOL . 'd, e,f  '],
            [[['foo' => 'bar', 'bar' => 'baz']], 'foo,bar' . PHP_EOL . 'bar,baz', true],
            [[['a', 'b', 'c'], ['d', 'e', 'f']], 'a,b,"c"' . PHP_EOL . 'd,"e",f'],
            [[['a', 'b', '1,2'], ['d', 'e', 'f']], 'a,b,"1,2"' . PHP_EOL . 'd,"e",f'],
            [[['foo' => 'bar', 'bar' => 'baz', 'faz' => null]], 'foo,bar,faz' . PHP_EOL . 'bar,baz', true],
            [[['foo' => 'bar', 'bar' => 'baz', 'faz' => 'faz']], 'foo,bar,faz' . PHP_EOL . 'bar,baz,faz,raz', true],
            [[['a', 'b', 'c'], ['d', 'e', 'f']], 'a	b	c' . PHP_EOL . 'd	e	f', false, '	'],
            [[['a', 'b', 'c'], ['d', 'e', 'f']], 'a,b,\'c\'' . PHP_EOL . 'd,\'e\',f', false, ',', '\''],
        ];
    }
    
    /**
     * @param           $a
     * @param           $b
     * @param   bool    $c
     * @param   string  $d
     * @param   string  $e
     *
     * @dataProvider _testFromCsvDataProvider
     */
    public function testFromCsv($a, $b, $c = false, $d = ',', $e = '"'): void
    {
        self::assertEquals($a, Arrays::makeFromCsv($b, $c, $d, $e));
    }
    
    public function _testFromCsvFailDataProvider(): array
    {
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
    public function testFromCsvFail($a): void
    {
        $this->expectException(ArrayGeneratorException::class);
        Arrays::makeFromCsv($a);
    }
    
    public function _testFromJsonDataProvider(): array
    {
        return [
            [[], []],
            [[], ''],
            [[], null],
            [[123, 'foo', 'bar'], '[123,"foo","bar"]'],
            [['foo' => 'bar', 'bar' => 'baz'], '{"foo":"bar","bar":"baz"}'],
        ];
    }
    
    /**
     * @param $a
     * @param $b
     *
     * @dataProvider _testFromJsonDataProvider
     */
    public function testFromJson($a, $b): void
    {
        self::assertEquals($a, Arrays::makeFromJson($b));
    }
    
    public function _testFromJsonFailDataProvider(): array
    {
        return [
            ['123'],
            ['"123"'],
            ['NULL'],
            [new DummyClass()],
            ['{\"foo\":bar\"}'],
        ];
    }
    
    /**
     * @param $a
     *
     * @dataProvider _testFromJsonFailDataProvider
     */
    public function testFromJsonFail($a): void
    {
        $this->expectException(ArrayGeneratorException::class);
        Arrays::makeFromJson($a);
    }
}
