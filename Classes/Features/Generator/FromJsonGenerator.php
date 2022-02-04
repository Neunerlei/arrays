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
use Throwable;

class FromJsonGenerator
{
    public function generate($input, array $options = []): array
    {
        if (is_array($input)) {
            return $input;
        }
        
        if (empty($input)) {
            return [];
        }
        
        if (! is_string($input)) {
            throw new ArrayGeneratorException('The given input is not supported as JSON array source!');
        }
        
        $input = trim($input);
        if ($input[0] !== '{' && $input[0] !== '[') {
            throw new ArrayGeneratorException('The given input is a string, but has no array as JSON data, so its no supported array source!');
        }
        
        $jsonOptions = $options['options'] ?? 0;
        $jsonOptions |= JSON_THROW_ON_ERROR;
        
        try {
            /** @noinspection JsonEncodingApiUsageInspection */
            $data = @json_decode(
                $input,
                (bool)($options['assoc'] ?? true),
                (int)($options['depth'] ?? 512),
                $jsonOptions
            );
        } catch (Throwable $e) {
            throw new ArrayGeneratorException('Error generating json: ' . $e->getMessage(), $e->getCode(), $e);
        }
        
        return $data;
    }
}
