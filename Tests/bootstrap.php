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
 * Last modified: 2020.03.02 at 20:53
 */
declare(strict_types=1);

namespace Neunerlei\Arrays\Tests\Assets;

use Neunerlei\Arrays\ArrayPaths;
use Neunerlei\Arrays\Arrays;

class DummyArraysAdapter extends Arrays {
	public static function getInstances(): array {
		return Arrays::$instances;
	}
	
	public static function flushInstances(): void {
		Arrays::$instances = [];
	}
	
	public static function makeInstance(string $class): object {
		return Arrays::getInstance($class);
	}
}

class DummyArrayPathsAdapter extends ArrayPaths {
	
	public static function getWalkerStep(array $list, array $path): array {
		$i = new ArrayPaths();
		return $i->initWalkerStep($list, $path);
	}
	
}

class DummyDumper {

}

class DummyIterator implements \Iterator {
	public $c    = 0;
	public $list = ["foo" => "bar", "true" => TRUE];
	
	public function current() {
		return array_values($this->list)[$this->c];
	}
	
	public function next() {
		$this->c++;
	}
	
	public function key() {
		return array_keys($this->list)[$this->c];
	}
	
	public function valid() {
		return $this->c < count($this->list);
	}
	
	public function rewind() {
		$this->c = 0;
	}
}

class DummyClass {
	public $foo = TRUE;
	public $bar = "baz";
}

class DummyToString {
	public function __toString() {
		return "foo,bar,baz";
	}
}