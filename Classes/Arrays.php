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
 * Last modified: 2020.02.27 at 10:57
 */
declare(strict_types=1);

namespace Neunerlei\Arrays;


use Neunerlei\Arrays\Traits\BasicTrait;
use Neunerlei\Arrays\Traits\DumperTrait;
use Neunerlei\Arrays\Traits\GeneratorTrait;
use Neunerlei\Arrays\Traits\ListTrait;
use Neunerlei\Arrays\Traits\PathTrait;

class Arrays
{
    /**
     * If this value is used in a getList value key, the second part will be used as "alias"
     */
    public const GET_LIST_ALIAS_SEPARATOR = ' as ';

    /**
     * This value is used as a separator for path elements in a string path
     */
    public const DEFAULT_PATH_SEPARATOR = '.';

    /**
     * The list of additional characters that can be escaped when a path is parsed
     */
    protected const ESCAPABLE_CHARS
        = [
            '*',
            '[',
            ']',
            ',',
        ];

    /**
     * The different key types
     */
    protected const KEY_TYPE_DEFAULT  = 0;
    protected const KEY_TYPE_WILDCARD = 1;
    protected const KEY_TYPE_KEYS     = 2;

    use BasicTrait;
    use PathTrait;
    use ListTrait;
    use GeneratorTrait;
    use DumperTrait;
}
