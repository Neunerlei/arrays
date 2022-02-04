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


use Neunerlei\Arrays\ArrayGeneratorException;

class FromStringListGenerator
{
    protected $separator = ',';
    protected $convertTypes = true;
    protected $strictNumerics = false;
    
    public function generate($input, array $options): array
    {
        if (is_array($input)) {
            return $input;
        }
        
        if (empty($input) && $input !== 0) {
            return [];
        }
        
        if (! is_string($input) && ! is_numeric($input)
            && ! (is_object($input) && method_exists($input, '__toString'))
        ) {
            throw new ArrayGeneratorException(
                'The given input ' . gettype($input) . ' is not supported as STRING array source!');
        }
        
        $this->readOptions($options);
        
        $parts = preg_split(
            '~(?<!\\\)' . preg_quote($this->separator, '~') . '~',
            trim((string)$input), -1,
            PREG_SPLIT_NO_EMPTY
        );
        
        return array_values(
            array_filter(
                array_map([$this, 'mapper'], $parts),
                static function ($v) { return $v !== ''; }
            )
        );
    }
    
    protected function readOptions(array $options): void
    {
        $this->separator = (string)($options['separator'] ?? ',');
        $this->convertTypes = (bool)($options['convertTypes'] ?? true);
        $this->strictNumerics = (
                                    isset($options['strictNumerics'])
                                    && $options['strictNumerics'] === true
                                )
                                || in_array('strictNumerics', $options, true);
    }
    
    protected function mapper(string $value)
    {
        if (stripos($value, $this->separator) !== false) {
            $value = str_replace('\\' . $this->separator, $this->separator, $value);
        }
        
        $value = trim($value);
        
        if (! $this->convertTypes) {
            return $value;
        }
        
        return $this->typeConverter($value);
    }
    
    protected function typeConverter(string $value)
    {
        $valueLower = strtolower($value);
        
        if ($valueLower === 'null') {
            return null;
        }
        
        if ($valueLower === 'false') {
            return false;
        }
        
        if ($valueLower === 'true') {
            return true;
        }
        
        if (
            is_numeric($valueLower)
            && (
                ! $this->strictNumerics
                || preg_match('~^[0-9.]*$~', $valueLower)
            )
        ) {
            return strpos($valueLower, '.') !== false ? ((float)$value) : ((int)$value);
        }
        
        return $value;
    }
}
