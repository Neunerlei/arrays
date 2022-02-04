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

use Iterator;
use Neunerlei\Arrays\Arrays;

class FixtureArraysAdapter extends Arrays
{
    public static function clearPathCache(): void
    {
        Arrays::$pathCache = [];
        Arrays::$pathCacheLimiter = [];
    }
    
    public static function getPathCacheData(): array
    {
        return [Arrays::$pathCache, Arrays::$pathCacheLimiter];
    }
    
    public static function callMethod(string $method, ...$args)
    {
        return Arrays::$method(...$args);
    }
}

class DummyIterator implements Iterator
{
    public $c = 0;
    public $list = ["foo" => "bar", "true" => true];
    
    public function current()
    {
        return array_values($this->list)[$this->c];
    }
    
    public function next()
    {
        $this->c++;
    }
    
    public function key()
    {
        return array_keys($this->list)[$this->c];
    }
    
    public function valid()
    {
        return $this->c < count($this->list);
    }
    
    public function rewind()
    {
        $this->c = 0;
    }
}

class DummyClass
{
    public $foo = true;
    public $bar = "baz";
}

class DummyToString
{
    public function __toString()
    {
        return "foo,bar,baz";
    }
}
