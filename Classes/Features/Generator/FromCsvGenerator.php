<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.02.01 at 14:54
 */

declare(strict_types=1);


namespace Neunerlei\Arrays\Features\Generator;


use Neunerlei\Arrays\ArrayGeneratorException;

class FromCsvGenerator
{
    public function generate(
        $input,
        bool $firstLineKeys = false,
        string $delimiter = ',',
        string $quote = '"'
    ): array {
        if (is_array($input)) {
            return $input;
        }

        if (empty($input)) {
            return [];
        }

        if (! is_string($input)) {
            throw new ArrayGeneratorException('The given input is not supported as CSV array source!');
        }

        $lines     = preg_split('/$\R?^/m', trim($input));
        $keyLength = 0;

        if ($firstLineKeys) {
            $keys      = array_shift($lines);
            $keys      = str_getcsv($keys, $delimiter, $quote);
            $keys      = array_map('trim', $keys);
            $keyLength = count($keys);
        }

        foreach ($lines as $ln => $line) {
            $line = str_getcsv($line, $delimiter, $quote);
            $line = array_map('trim', $line);

            // No keys
            if (! isset($keys)) {
                $lines[$ln] = $line;
                continue;
            }

            // Keys match
            if (count($line) === $keyLength) {
                $lines[$ln] = array_combine($keys, $line);
                continue;
            }

            // Apply key length to line
            $lines[$ln] = array_combine($keys, array_pad(array_slice($line, 0, $keyLength), $keyLength, null));
        }

        return $lines;
    }
}
