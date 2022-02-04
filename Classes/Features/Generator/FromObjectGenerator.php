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
use Iterator;
use Neunerlei\Arrays\ArrayGeneratorException;
use SimpleXMLElement;
use stdClass;

class FromObjectGenerator
{
    /**
     * @var \Neunerlei\Arrays\Features\Generator\FromXmlGenerator|null
     */
    protected $xmlGenerator;
    
    public function setXmlGenerator(FromXmlGenerator $fromXmlGenerator): void
    {
        $this->xmlGenerator = $fromXmlGenerator;
    }
    
    public function generate($input): array
    {
        if (is_array($input)) {
            return $input;
        }
        
        if (empty($input)) {
            return [];
        }
        
        if ($input instanceof DOMNode || $input instanceof SimpleXMLElement) {
            return ($this->xmlGenerator ?? (new FromXmlGenerator()))->generate($input);
        }
        
        if ($input instanceof Iterator || $input instanceof stdClass) {
            $out = [];
            foreach ($input as $k => $v) {
                $out[$k] = $v;
            }
            
            return $out;
        }
        
        if (is_object($input)) {
            // @todo in PHP7.4 this will no longer find properties that are not initialized
            // should we migrate to reflection instead?
            return get_object_vars($input);
        }
        
        throw new ArrayGeneratorException('The given input is not supported as OBJECT array source!');
    }
}
