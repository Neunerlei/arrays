# Array Generation
Arrays can be serialized and unserialized into and from a variety of different formats. The array generation part of the library gives you some tools for the most commonly used formats.

## makeFromXml()
Receives a xml-input and converts it into a multidimensional array.
The core of the method is heavily inspired (\*cough\*) by [CakePhp's XML implementation](https://github.com/cakephp/utility/blob/master/Xml.php)
with a few extra twirks to make it more convenient.

::: tip
It accepts either an XML String (With or without header), a DOMNode or a SimpleXMLElement as an input. The output is an array containing
a list of nodes that may have one of four types of children:

 * "tag": Describes the tag of the node
 * "@attr": The name of an attribute and it's value with an "@" to describe it
 * "content": The content value of the node as a string.
 * "childNode": Has a numeric index and contains nested nodes of the current node.
:::

::: warning
The generation of "associative" arrays based on xml's is currently a feature in active development.
It might change in the future. Suggestions are welcome.
::: 

::: details Arguments
- $input The xml source to convert into an array
- $asAssocArray If this is set to true the result object is converted to a more readable associative array. Be careful with this! There might be sideEffects, like changing paths when the result array has a changing number of nodes.
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::makeFromXml("<body><node>foo</node><node>bar</node></body>");
Arrays::makeFromXml(new SimpleXMLElement("..."));
```

## makeFromObject()
The method receives an object of any kind and converts it into a multidimensional array
```php
use Neunerlei\Arrays\Arrays;
use Neunerlei\Arrays\Tests\Assets\DummyClass;
use Neunerlei\Arrays\Tests\Assets\DummyIterator;

Arrays::makeFromObject(new DummyIterator());
// ["foo" => "bar", "true" => TRUE]
Arrays::makeFromObject(new DummyClass());
// ["foo" => true, "bar" => "baz"]
Arrays::makeFromObject(new SimpleXMLElement("..."));
// The same output as makeFromXml() would give you
```

## fromStringList()
Receives a string list like: "1,asdf,foo, bar" which will be converted into [1, "asdf", "foo", "bar"]
Note the automatic trimming and value conversion of numbers, TRUE, FALSE an null.
By default the separator is ",". All separators can be escaped using "\\"

::: details Arguments
- $input     The value to convert into an array
- $separator The separator to split the string at
:::

```php
use Neunerlei\Arrays\Arrays;
Arrays::makeFromStringList("abc,foo,bar"); // ["abc", "foo", "bar"]
Arrays::makeFromStringList("foo\\,bar"); // ["foo,bar"]
Arrays::makeFromStringList("true,FALSE,TRUE,1,0,NULL,123"); 
// [TRUE, FALSE, TRUE, 1, 0, NULL, 123]
```

## makeFromCsv()
Receives a string value and parses it as a csv into an array.

::: details Arguments
- $input         The csv string to parse
- $firstLineKeys Set to true if the first line of the csv are keys for all other rows
- $delimiter     The delimiter between multiple fields
- $quote         The enclosure or quoting tag
:::

```php
use Neunerlei\Arrays\Arrays;
$data = "a,b,c
d,e,f";

Arrays::makeFromCsv($data); 
// [["a", "b", "c"], ["d", "e", "f"]]

Arrays::makeFromCsv($data, true); 
// [["a" => "d", "b" => "e", "c" => "f"]]
```

## makeFromJson()
Creates an array out of a json data string. Throws an exception if an error occurred!
**Only works with json objects or arrays. Other values will throw an exception!**

```php
use Neunerlei\Arrays\Arrays;
Arrays::makeFromJson("[123,\"foo\",\"bar\"]"); 
// [123, "foo", "bar"]
```
