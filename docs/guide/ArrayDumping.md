# Array Dumping
The array dumper allows you to serialize an array into different stringified formats.

## dumpToJson()
Dumps the given array as JSON string.
Simply a wrapper around json_encode to throw errors if the encoding fails.

::: details Arguments
- pretty    If set to TRUE the JSON will be generated pretty printed
- options   Bitmask consisting of one or multiple of the JSON_ constants. The behaviour of these constants is described on the JSON constants page. JSON_THROW_ON_ERROR is set by default for all operations
- depth     User specified recursion depth.
:::

```php
use Neunerlei\Arrays\Arrays;
$data = ['foo' => 'bar', 'bar' => 'baz'];

Arrays::dumpToJson($data);
// {"foo":"bar","bar":"baz"}

Arrays::dumpToJson($data, ['pretty']);
// {
//     "foo": "bar",
//     "bar": "baz"
// }

```

## dumpToXml()
[WIP] This is the counterpart of Arrays::makeFromXml() which takes it's output
and converts it back into a stringified XML format.

This method works, but is still considered work in progress! Use with care!
