# General Helpers
One major feature of the package is the handling of rudimentary, array related actions that are not implemented in the php core.

## isAssociative()
Returns true if the given array is an associative array
Associative arrays have string keys instead of numbers!
```php
use Neunerlei\Arrays\Arrays;
Arrays::isAssociative(["foo" => "bar"]); // TRUE
```

## isSequential()
Returns true if the given array is sequential.
Sequential arrays are numeric and in order like 0 => 1, 1 => 2, 2 => 3.
```php
use Neunerlei\Arrays\Arrays;
Arrays::isSequential(["foo", "bar", "baz"]); // TRUE
Arrays::isSequential([0 => "foo", 2 => "bar", 4 => "baz"]); // FALSE
Arrays::isSequential(["foo" => "bar"]); // FALSE
```

## isArrayList()
Returns true if the given array is a numeric list of arrays.
```php
use Neunerlei\Arrays\Arrays;
Arrays::isArrayList(["asdf" => 1]); // FALSE
Arrays::isArrayList(["asdf" => ["asdf"]]); // TRUE
Arrays::isArrayList([["asdf"], [123]]); // TRUE
```

## sortByStrLen()
Sorts the given list of strings by the length of the contained values.
::: details Arguments
- $list The array of strings you want to sort
- $asc  (FALSE) Set this to true if you want to sort ascending (shortest first)
:::
```php
use Neunerlei\Arrays\Arrays;
Arrays::sortByStrLen(["bar", "fooFoo", "fooFooFoo", "fooFoo"]); 
// Returns ["fooFooFoo", "fooFoo", "fooFoo", "bar"]

Arrays::sortByStrLen(["bar", "fooFoo", "fooFooFoo", "fooFoo"], true); 
// Returns ["bar", "fooFoo", "fooFoo", "fooFooFoo"]
```

## sortByKeyStrLen()
Sorts the given list by the length of the key's strings
Similar to sortByStrLen() but sorts by key instead of the value
::: details Arguments
- $list The array of strings you want to sort
- $asc  Default: False Set this to true if you want to sort ascending (shortest first)
:::
```php
use Neunerlei\Arrays\Arrays;
Arrays::sortByKeyStrLen(["aaa" => "aaa", "a" => "a", "aa" => "aa"]); 
// Returns ["a" => "a", "aa" => "aa", "aaa" => "aaa"]

Arrays::sortByKeyStrLen(["aaa" => "aaa", "a" => "a", "aa" => "aa"], true); 
// Returns ["aaa" => "aaa", "aa" => "aa", "a" => "a"]
```

## merge()
This method merges multiple arrays into each other. It will traverse elements recursively. While
traversing the second array ($b) all its values will be merged into the first array ($a). The values of $b will
overrule the values in $a. If both values are arrays the merge will go deeper and merge the child arrays into each
other.

::: tip
By default numeric keys will be merged into each other so: [["foo"]] + [["bar"]] becomes [["bar"]].
This however is only the case for ARRAYS! All other values will be appended to $a, so ["a"] + ["b"] becomes
["a", "b"]. You can use the "strictNumericMerge" and "noNumericMerge" flags to control the behaviour directly.
:::

::: tip
It is possible to remove keys from an array while they are merge by using the __UNSET special value.
Keep in mind, that the "allowRemoval" flag has to be enabled for that.
:::

::: details Arguments
The method receives a list of arrays that should be merged with each other. The list can contain the following strings to act as FLAGS to modify the behaviour of
the method:
- **strictNumericMerge | sn**: By default only arrays with numeric keys are merged into each
other. By setting this flag ALL values will be merged into each other when they have
numeric keys.
- **noNumericMerge | nn**: Disables the merging of numeric keys. See NOTE above
- **allowRemoval | r**: Enables the value "__UNSET" feature, which can be used in the merged
array in order to unset array keys in the original array.
:::

```php
use Neunerlei\Arrays\Arrays;
// Normal merging
Arrays::merge(["foo"], ["a", "b", "c"]); // ["foo", "a", "b", "c"]
Arrays::merge(["a"], ["b", "c"]); // ["a", "b", "c"]

// Recursive merging
Arrays::merge(["a" => []], ["a" => ["b" => ["c" => "c"]]], ["a" => ["d" => "d"]]);
// ["a" => ["b" => ["c" => "c"], "d" => "d"]]

// Strict numeric merge
Arrays::merge(["foo"], ["a", "b", "c"], "sn"); // ["a", "b", "c"]

// No numeric merge
Arrays::merge(["foo"], ["a", "b", "c"], "noNumericMerge"); // ["foo", "a", "b", "c"]

// Unset children
Arrays::merge(["foo" => "bar", "bar" => "baz"], ["bar" => "__UNSET"], "r");
// ["foo" => "bar"]
```

## attach()
This helper can be used to attach one array to the end of another.
This is basically [...] + [...] but without overriding numeric keys
```php
use Neunerlei\Arrays\Arrays;
Arrays::attach(["a"], ["b"], ["c"]); // ["a", "b", "c"]
```

## renameKeys()
This method can rename keys of a given array according to a given map
of ["keyToRename" => "RenamedKey"] as second parameter. Keys not present in $list will be ignored.
```php
use Neunerlei\Arrays\Arrays;
Arrays::renameKeys(["a" => "a", "b" => "b"], ["a" => "c"]); // ["c" => "a", "b" => "b"]
```

## insertAt()
Adds the given key ($insertKey) and value ($insertValue) pair either BEFORE or AFTER a $pivotKey in a $list.

::: details Arguments
- $list         The list to add the new $insertValue to
- $pivotKey     The pivot key that is used as reference on where to insert the new value
- $insertKey    The new key to set for the given $insertValue
- $insertValue  The value to add to the given list
- $insertBefore By default the $insertValue is inserted AFTER the $pivotKey 
set this to TRUE to insert it BEFORE the $pivotKey instead
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::insertAt(["a" => 1, "b" => 2], "b", 3, "c");
// Returns ["a" => 1, "b" => 2, "c" => 3]

Arrays::insertAt(["a" => 1, "b" => 2], "b", 3, "c", true);
// Returns ["a" => 1, "c" => 3, "b" => 2]
```

## shorten()
Tiny helper which will shorten a multidimensional array until it's smallest element.
This is especially useful for database results.
```php
use Neunerlei\Arrays\Arrays;
Arrays::shorten(["foo" => [["bar" => 123]]]); // 123
Arrays::shorten([[["foo" => [[["foo" => "foo", "bar" => "bar"]]]]]]); 
// Returns ["foo" => "foo", "bar" => "bar"]
```

## getSimilarKey()
Searches the most similar key to the given needle from the haystack
```php
use Neunerlei\Arrays\Arrays;
Arrays::getSimilarKey(["foo" => TRUE, "bar" => TRUE, "baz" => TRUE], "fao"); // foo
```

## sortBy()
Sorts a given multidimensional array by either a key or a path to a key, by keeping
the associative relations like asort would.

::: details Arguments
- $list   The array to sort
- $key     Either the key or the path to sort by
- $options Additional config options:
    - separator: (".") The separator between the parts if path's are used in $key
    - desc: (FALSE) By default the method sorts ascending. To change to descending,
set this to true
:::

```php
use Neunerlei\Arrays\Arrays;
$data = [
    "asdf" => [
        "key" => 2,
        "sub" => [
            "key" => 2,
        ],
    ],
    "cde"  => [
        "key" => 1,
        "sub" => [
            "key" => 3,
        ],
    ],
];
Arrays::sortBy($data, "key"); // Key order: cde, asdf
Arrays::sortBy($data, "sub.key"); // Key order: asdf, cde
```

## without()
Removes the given list of keys / paths from the $input array and returns the results

::: details Arguments
- $list          The array to strip the unwanted fields from
- $pathsToRemove The keys / paths to remove from $input
- $options       Additional config options
    - separator (".") Can be set to any string you want to use as separator of path parts.
    - removeEmpty (TRUE) Set this to false to disable the automatic cleanup of empty remains when the lowest child was removed from a tree.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::without(["true" => TRUE, "foo" => TRUE], ["true"]); // ["foo" => TRUE]
Arrays::without([["foo" => "foo", "bar" => "bar"], ["foo" => "foo", "bar" => "bar"]], 
    ["*.bar"]); // [["foo" => "foo"], ["foo" => "foo"]]
```

## flatten()
Flattens a multidimensional array into a one dimensional array, while keeping their keys as "path". So for example:

::: details Arguments
- $list    The array or iterable to flatten
- $options Additional config options:
    - separator (string) default ".": Is used to define the separator that glues the "key's" of the path together
    - arraysOnly (bool) default FALSE: By default this method traverses all kinds of iterable objects as well as arrays. If you only want to traverse arrays set this to TRUE
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::flatten(["foo" => 123, "bar" => ["baz" => 234]]); 
// Returns ["foo" => 123, "bar.baz" => 234]
```

## unflatten()
Basically the reverse operation of flatten(). Converts a flattened, one-dimensional array into a multidimensional array, using
their keys as "path". 

::: details Arguments
- $list    The flattened list to inflate
- $options Additional config options:
    - separator (string) default ".": Is used to define the separator that glues the "key's" of the path together
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::unflatten(["foo" => 123, "bar.baz" => 234]); 
// Returns ["foo" => 123, "bar" => ["baz" => 234]]
```

## mapRecursive()
Works exactly like array_map but traverses the array recursively.

::: tip
You callback will get the following arguments:
$currentValue, $currentKey, $pathOfKeys, $inputArray
:::

::: details Arguments
- $list     The array to iterate
- $callback The callback to execute for every child of the given array.
:::

```php
use Neunerlei\Arrays\Arrays;
$data = [
    "foo" => "bar",
    "bar" => [
        "baz" => [
            "foo" => 123,
        ],
    ],
];
Arrays::mapRecursive($data, function(){ return 1; }); 
// Returns ["foo" => 1, "bar" => ["baz" => ["foo" => 1]]]
```

## getList()
This is a multi purpose tool to handle different scenarios when dealing with array lists.
It expects a list of similarly structured arrays from which data should be extracted.
But it's probably better to show than to tell, here is what it can do:

::: details Arguments
- $input     The input array to gather the list from. Should be a list of arrays.
- $valueKeys The list of value keys to extract from the input list, or a single key as a string, can contain sub-paths like seen in example 4
- $keyKey    Optional key or sub-path which will be used as key in the result array
- $options   Additional configuration options:
    - default (mixed) NULL: The default value if a key was not found in $input.
    - separator (string) ".": A separator which is used when splitting string paths
:::

::: details Example Data
```php
$data = [
    [
        "id"    => "234",
        "title" => "medium",
        "asdf"  => "asdf",
        "array" => [
            "id"     => "12",
            "rumpel" => "di",
            "bar"    => "baz",
        ],
    ],
    [
        "id"    => "123",
        "title" => "apple",
        "asdf"  => "asdf",
        "array" => [
            "id"     => "23",
            "rumpel" => "pumpel",
            "foo"    => "bar",
        ],
    ],
];
```
:::

```php
use Neunerlei\Arrays\Arrays;

// Example 1: Return a list of all "id" values
Arrays::getList($data, "id"); // ["234","123"];

// Example 2: Return a list of all "id" and "title" values
Arrays::getList($data, ["id", "title"]);
// [["id" => "234", "title" => "medium"], [ "id" => "123", "title" => "apple"]];

// Example 3: Return a list of all "title" values with their "id" as key
Arrays::getList($data, "title", "id");
// ["234" => "medium", "123" => "apple"];

// Example 4: Path lookup and aliases for longer keys
Arrays::getList($data, ["array.id", "array.rumpel as myAlias"], "id");
// ["234" => ["array.id" => "12", "myAlias" => "di"],
// "123" => ["array.id" => "23", "myAlias" => "pumpel"]]; 

// Example 5: Path lookup and default value for unknown keys
Arrays::getList($data, ["array.id", "array.bar"], "id");
// [
//     "234" => ["array.id" => "12", "array.bar" => "baz"],
//     "123" => ["array.id" => "23", "array.bar" => null] <-- NULL because not value was found!
// ];

// Example 6: Keep the rows identical but use a column value as key in the result array
Arrays::getList($data, null, "id");
// "234" => [array...], "123" => [array...]]; 

// Example 7: Dealing with path based key lookups
Arrays::getList($data, "id", "array.id");
// ["12" => "234", "23" => "123"];

```