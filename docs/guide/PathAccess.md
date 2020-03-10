# Path Access
When working with arrays you will learn quite soon, that it is not enough to write ```isset($array["not"]["existing"]["path"])``` without spamming the console with notices about undefined indexes in an array.

Or you want to narrow a big array down to a more reasonable size, you might want to use the path lookup feature to extract only the data that is required.

## Path Definition
A path can be defined in two different ways. The first option is to write a path as a string **"my.path.there"**. As you see, the parts of the path are delimited by a period, as an array notation you would write: **["my"]["path"]["there"]** for the same result.

The second option is to write a path as an array like **["my", "path", "there"]**, this can be used if you work with dynamic path segments or want to save the time parsing the string version into the array. Under the hood every string path will be parsed into the array equivalent before traversing the array. 

```php {6}
// Select an element in a list: "key.child.bar"
$list = [
    "key" => [
        "foo" => "bar",
        "child" => [
            "bar" => "bar"
        ]
    ]
];
```

#### Wildcard paths
If you have a list of repeated sub-arrays, or simply want to select all children of an associative array, you can use a wildcard selector in your path. 
By using an **"\*"** as path segment the script will try to iterate over all children. This works for array - like paths as well.

```php {4,5}
// Select all children of a node: "key.*"
$list = [
    "key" => [
        "foo" => "bar",
        "bar" => "baz",
    ]
];
```

```php {3,4}
// Select all nodes in a list "*"
$list = [
    [/*...*/],
    [/*...*/],
];
```

#### Subset of paths
It is possible to grab only a subset of elements when a path is used to get data from an array.
You can do so by putting the names of the required fields into brackets.

```php {5,6}
// Select some of the children in a node: "key.[bar,baz]"
$list = [
    "key" => [
        "foo" => "bar",
        "bar" => "bar",
        "baz" => "baz",
        "bob" => "bob",
    ]
];
```

It is also possible to select deeper subsets
```php {6,9}
// Select some of the children in a node: "key.[bar.foo,baz]"
$list = [
    "key" => [
        "foo" => "bar",
        "bar" => [
            "bar" => "bar",
            "foo" => "foo",
        ],
        "baz" => "baz",
        "bob" => "bob",
    ]
];
```

## parsePath()
This method is used to convert a string into a path array. It will also validate already existing path arrays.
By default a period (.) is used to separate path parts like: ```"my.array.path" => ["my","array","path"]```.
If you require another separator you can set another one by using the $separator parameter.
In most circumstances it will make more sense just to escape a separator, tho. Do that by using a backslash like:
```"my\.array.path" => ["my.array", "path"]```.

::: details Arguments
- $path      The path to parse as described above.
- $separator "." Can be set to any string you want to use as separator of path parts.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::parsePath("123"); // ["123"] 
Arrays::parsePath("foo.bar.baz"); // ["foo", "bar", "baz"] 
Arrays::parsePath("foo.[bar,baz,foo]"); // ["foo", ["bar", "baz", "foo"]]
Arrays::parsePath("[wild,wild2].*.horse.[carrot, orange]");
// [[["wild", "wild2"], "*", "horse", ["carrot", "orange"]]
```

## mergePath()
This method can be used to merge two paths together. This becomes useful if you want to work with a dynamic part in form of an array
and a static string part. The result will always be a path array. You can specify a separator type for each part of the given path if you merge differently formatted paths.

::: details Arguments
- $pathA      The path to add $pathB to
- $pathB      The path to be added to $pathA
- $separatorA The separator for string paths in $pathA
- $separatorB The separator for string paths in $pathB
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::mergePaths("a.path.to.", ["parts","inTheTree"]); 
// ["a", "path", "to", "parts", "inTheTree"]
Arrays::mergePaths("a.b.*", "c.d.[asdf,id]"); 
// ["a", "b", "*", "c", "d", ["asdf", "id"]
Arrays::mergePaths("a.b", "c,d", ".", ","); // ["a","b","c","d"]
```

## hasPath()
This method checks if a given path exists in a given $input array

::: details Arguments
- $input     The array to check
- $path      The path to check for in $input
- $separator "." Can be set to any string you want to use as separator of path parts.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::getPath(["foo" => 123, "bar" => "foo"], "bar"); // TRUE
Arrays::getPath(["foo" => 123, "bar" => "foo"], "baz"); // FALSE
Arrays::getPath(["foo" => ["bar" => "baz"]], "foo.bar"); // TRUE
Arrays::getPath(["foo" => ["bar" => "baz"]], "foo-bar", "-"); // TRUE
```

## getPath()
This method reads a single value or multiple values (depending on the given $path) from the given $input array.

::: details Arguments
- $input     The array to read the path's values from
- $path      The path to read in the $input array
- $default   The value which will be returned if the $path did not match anything.
- $separator "." Can be set to any string you want to use as separator of path parts.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::getPath(["foo" => 123, "bar" => "foo"], "bar"); // "foo"
Arrays::getPath(["foo" => 123, "bar" => "foo"], "baz"); // NULL
Arrays::getPath(["foo" => 123, "bar" => "foo"], "baz", FALSE); // FALSE
Arrays::getPath(["foo" => ["bar" => "baz"]], "foo.bar"); // "baz"
Arrays::getPath([["foo" => 1], ["foo" => 2]], "*.foo"); // [1,2]
```

## setPath()
This method lets you set a given value at a path of your array.
You can also set multiple keys to the same value at once if you use wildcards.

::: details Arguments
- $input     The array to set the values in
- $path      The path to set $value at
- $value     The value to set at $path in $input
- $separator "." Can be set to any string you want to use as separator of path parts.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::setPath(["foo" => 123, "bar" => "foo"], "bar", "baz");
// ["foo" => 123, "bar" => "baz"]
Arrays::setPath(["foo" => 123, "bar" => "foo"], "baz", "baz");
// ["foo" => 123, "bar" => "foo", "baz" => "baz"]
Arrays::setPath(["foo" => ["bar" => "baz"]], "foo.bar", true); 
// ["foo" => ["bar" => true]]
Arrays::setPath([["foo" => 1], ["foo" => 2]], "*.foo", 123); 
// [["foo" => 123], ["foo" => 123]]
```

## removePath()
Removes the values at the given $path's from the $input array.
It can also remove multiple values at once if you use wildcards.

::: tip
The method tries to remove empty remains recursively when the last
child was removed from the branch. If you don"t want to use this behaviour
set $removeEmptyRemains to false.
:::

::: details Arguments
- $input   The array to remove the values from
- $path    The path which defines which values have to be removed
- $options Additional config options
    - separator (string) ".": Can be set to any string you want to use as separator of path parts.
    - keepEmpty (bool) TRUE: Set this to false to disable the automatic cleanup of empty remains when the lowest child was removed from a tree.
:::


```php
use Neunerlei\Arrays\Arrays;
Arrays::removePath(["foo" => 123, "bar" => "foo"], "bar");
// ["foo" => 123]
Arrays::removePath(["foo" => 123, "bar" => "foo"], "baz");
// ["foo" => 123, "bar" => "foo"]
Arrays::removePath(["foo" => ["bar" => "baz"]], "foo.bar"); 
// [] -> Empty values will be removed
Arrays::removePath(["foo" => ["bar" => "baz"]], "foo.bar", ["keepEmpty"]); 
// ["foo" => []]
Arrays::removePath([["foo" => 1, "bar" => "foo"], ["foo" => 2]], "*.foo"); 
// [["bar" => "foo"]]
```

## filterPath()
This method can be used to apply a filter to all values the given $path matches.
The callback should always return void.

::: tip
You callback will get the following arguments:
$currentValue, $currentKey, $pathOfKeys, $inputArray
:::

::: details Arguments
- $input     The array to filter
- $path      The path which defines the values to filter
- $callback  The callback to trigger on every value found by $path
- $separator "." Can be set to any string you want to use as separator of path parts.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::filterPath([["foo" => 1], ["foo" => 2]], "*.foo", function(
    $value, $key, $path, $inputArray){
    // Filter your list
});
```