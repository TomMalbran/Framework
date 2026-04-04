<?php
namespace Tests\Utils;

use Framework\Utils\Arrays;

use PHPUnit\Framework\TestCase;

class ArraysTest extends TestCase {

    public function testIsList() {
        // only numeric keys starting from 0 are considered lists
        $this->assertTrue(Arrays::isList([ 1, 2, 3 ]));

        // non-numeric keys or numeric keys that don't start from 0 are not lists
        $this->assertFalse(Arrays::isList([ "a" => 1, "b" => 2 ]));

        // null or non-arrays are not lists
        $this->assertFalse(Arrays::isList(null));

        // empty array is considered a list
        $this->assertTrue(Arrays::isList([]));
    }

    public function testIsArrayList() {
        // only arrays of arrays with numeric keys are considered array lists
        $this->assertTrue(Arrays::isArrayList([[ 1 ], [ 2 ]]));

        // non-array values or non-numeric keys are not array lists
        $this->assertFalse(Arrays::isArrayList([ "a" => [ "x" ]]));

        // null or non-arrays are not array lists
        $this->assertFalse(Arrays::isArrayList(null));
        $this->assertFalse(Arrays::isArrayList([]));

        // list of non-arrays is not an array list
        $this->assertFalse(Arrays::isArrayList([ 1, 2 ]));
    }

    public function testIsDict() {
        // only arrays with string keys are considered dicts
        $this->assertTrue(Arrays::isDict([ "a" => 1 ]));

        // arrays with numeric keys are not dicts
        $this->assertFalse(Arrays::isDict([ 1, 2, 3 ]));

        // null or non-arrays are not dicts
        $this->assertFalse(Arrays::isDict(null));

        // empty array is not considered a dict
        $this->assertFalse(Arrays::isDict([]));
    }

    public function testIsMap() {
        // only arrays with string keys and array values are considered maps
        $this->assertTrue(Arrays::isMap([ "a" => [ "x" => 1 ] ]));

        // arrays with non-array values or numeric keys are not maps
        $this->assertFalse(Arrays::isMap([ "a" => 1 ]));

        // null or non-arrays are not maps
        $this->assertFalse(Arrays::isMap(null));

        // empty array is not considered a map
        $this->assertFalse(Arrays::isMap([]));
    }

    public function testToArray() {
        // arrays should be returned as-is
        $this->assertSame([ 1, 2 ], Arrays::toArray([ 1, 2 ]));

        // non-arrays should be wrapped in an array
        $this->assertSame([ "x" ], Arrays::toArray("x"));

        // null should return empty array
        $this->assertSame([], Arrays::toArray(null));

        // objects should be converted to arrays
        $obj = (object)[ "a" => 1 ];
        $this->assertSame([ "a" => 1 ], Arrays::toArray($obj));

        // arrays with non-numeric keys should be returned as-is
        $this->assertSame([ "a" => 1 ], Arrays::toArray([ "a" => 1 ]));
    }

    public function testToList() {
        // arrays with numeric keys starting from 0 should be returned as-is
        $this->assertSame([ 1 ], Arrays::toList(1));

        // arrays with non-numeric keys should return their values as a list
        $this->assertSame([ 1, 2 ], Arrays::toList([ "a" => 1, "b" => 2 ]));

        // null should return empty list
        $this->assertSame([], Arrays::toList(null));

        // non-arrays should be wrapped in a list
        $this->assertSame([ "x" ], Arrays::toList("x"));

        // objects should be converted to lists of their values
        $obj = (object)[ "a" => 1, "b" => 2 ];
        $this->assertSame([ 1, 2 ], Arrays::toList($obj));
    }

    public function testToObject() {
        // arrays should be returned as-is
        $this->assertSame([ "a" => 1 ], Arrays::toObject([ "a" => 1 ]));

        // null should return empty object
        $this->assertEquals((object)[], Arrays::toObject(null));

        // empty array should return empty object
        $this->assertEquals((object)[], Arrays::toObject([]));
    }

    public function testToInts() {
        // scalar int returns a single-element list
        $this->assertSame([ 1 ], Arrays::toInts(1));

        // scalar zero with withoutEmpty true should be filtered out
        $this->assertSame([], Arrays::toInts(0, "", true));

        // non-array, non-int returns empty list
        $this->assertSame([], Arrays::toInts("notarray"));

        // numeric strings and ints in an array are converted to ints
        $this->assertSame([ 1, 2 ], Arrays::toInts([ "1", 2 ]));

        // mixed values: non-numeric are skipped
        $this->assertSame([ 1 ], Arrays::toInts([ "1", "x" ]));

        // when using a key, the function extracts values from sub-rows
        $rows = [[ "v" => "3" ], [ "v" => "4" ], [ "v" => "" ], [ "v" => 0 ]];
        $this->assertSame([ 3, 4, 0 ], Arrays::toInts($rows, "v"));

        // withoutEmpty true should remove empty values (including 0)
        $this->assertSame([ 3, 4 ], Arrays::toInts($rows, "v", true));

        // works with objects too
        $rowsObj = [(object)[ "v" => "5" ], (object)[ "v" => "6" ]];
        $this->assertSame([ 5, 6 ], Arrays::toInts($rowsObj, "v"));
    }

    public function testToStrings() {
        // simple list of strings is preserved
        $this->assertSame([ "a", "b" ], Arrays::toStrings([ "a", "b" ]));

        // non-array non-string returns empty list
        $this->assertSame([], Arrays::toStrings(123));

        // scalar string returns single-element list
        $this->assertSame([ "s" ], Arrays::toStrings("s"));

        // empty scalar with withoutEmpty filters out
        $this->assertSame([], Arrays::toStrings("", "", true));

        // numeric values are converted to strings
        $this->assertSame([ "0" ], Arrays::toStrings([ 0 ]));

        // withoutEmpty removes values considered empty (0, "", null)
        $this->assertSame([], Arrays::toStrings([ 0 ], "", true));

        // works with key extraction from rows (arrays)
        $rows = [[ "v" => "x" ], [ "v" => "" ], [ "v" => null ], [ "v" => 5 ]];
        $this->assertSame([ "x", "", "", "5" ], Arrays::toStrings($rows, "v"));
        $this->assertSame([ "x", "5" ], Arrays::toStrings($rows, "v", true));

        // works with objects as rows and respects withoutEmpty
        $rowsObj = [(object)[ "v" => "a" ], (object)[ "v" => 0 ]];
        $this->assertSame([ "a", "0" ], Arrays::toStrings($rowsObj, "v"));
        $this->assertSame([ "a" ], Arrays::toStrings($rowsObj, "v", true));

        // passing a single object (not an array) returns empty list
        $this->assertSame([], Arrays::toStrings((object)[ "v" => "a" ]));
    }

    public function testToStringsMap() {
        // simple key/value conversion
        $this->assertSame([ "a" => "1" ], Arrays::toStringsMap([ "a" => 1 ]));

        // numeric keys become string keys
        $this->assertSame([ "1" => "v" ], Arrays::toStringsMap([ 1 => "v" ]));

        // string numeric keys are preserved
        $this->assertSame([ "1.23" => "x" ], Arrays::toStringsMap([ "1.23" => "x" ]));

        // null and non-arrays return empty map
        $this->assertSame([], Arrays::toStringsMap("x"));
        $this->assertSame([], Arrays::toStringsMap(null));

        // null values convert to empty string
        $this->assertSame([ "a" => "" ], Arrays::toStringsMap([ "a" => null ]));

        // object values convert via Strings::toString (typically empty)
        $this->assertSame([ "k" => "" ], Arrays::toStringsMap([ "k" => (object)[] ]));
    }

    public function testToIntStringMap() {
        // numeric string keys become int keys
        $this->assertSame([ 1 => "v" ], Arrays::toIntStringMap([ "1" => "v" ]));

        // integer keys are preserved
        $this->assertSame([ 2 => "x" ], Arrays::toIntStringMap([ 2 => "x" ]));

        // float-like numeric keys are converted to int (rounded)
        $this->assertSame([ 1 => "x" ], Arrays::toIntStringMap([ "1.23" => "x" ]));

        // non-numeric keys convert to 0
        $this->assertSame([ 0 => "a" ], Arrays::toIntStringMap([ "a" => "a" ]));

        // collisions: later value wins for the same int key
        $this->assertSame([ 1 => "b" ], Arrays::toIntStringMap([ "1" => "a", "1.0" => "b" ]));

        // values are converted to strings (including null -> empty string)
        $this->assertSame([ 1 => "2" ], Arrays::toIntStringMap([ "1" => 2 ]));
        $this->assertSame([ 1 => "" ], Arrays::toIntStringMap([ "1" => null ]));

        // object values become empty strings
        $this->assertSame([ 1 => "" ], Arrays::toIntStringMap([ "1" => (object)[] ]));

        // non-array input returns empty map
        $this->assertSame([], Arrays::toIntStringMap("x"));
    }

    public function testToStringIntMap() {
        // simple string numeric value converted to int
        $this->assertSame([ "k" => 1 ], Arrays::toStringIntMap([ "k" => "1" ]));

        // integer values preserved
        $this->assertSame([ "k" => 2 ], Arrays::toStringIntMap([ "k" => 2 ]));

        // float-like strings converted to int (rounded)
        $this->assertSame([ "k" => 1 ], Arrays::toStringIntMap([ "k" => "1.23" ]));

        // null and non-numeric convert to 0
        $this->assertSame([ "k" => 0 ], Arrays::toStringIntMap([ "k" => null ]));
        $this->assertSame([ "k" => 0 ], Arrays::toStringIntMap([ "k" => "x" ]));

        // object values convert to 0
        $this->assertSame([ "k" => 0 ], Arrays::toStringIntMap([ "k" => (object)[] ]));

        // non-array input returns empty map
        $this->assertSame([], Arrays::toStringIntMap("x"));
    }

    public function testToStringFloatMap() {
        // string numeric with decimals returns float
        $this->assertSame([ "k" => 1.23 ], Arrays::toStringFloatMap([ "k" => "1.23" ], 2));

        // integer interpreted with decimals is divided by padding
        $this->assertSame([ "k" => 1.23 ], Arrays::toStringFloatMap([ "k" => 123 ], 2));

        // float values are preserved
        $this->assertSame([ "k" => 1.23 ], Arrays::toStringFloatMap([ "k" => 1.23 ], 2));

        // numeric string without decimals becomes float of whole number
        $this->assertSame([ "k" => 123.0 ], Arrays::toStringFloatMap([ "k" => "123" ], 2));

        // null converts to zero
        $this->assertSame([ "k" => 0.0 ], Arrays::toStringFloatMap([ "k" => null ], 2));

        // non-array input returns empty map
        $this->assertSame([], Arrays::toStringFloatMap("x", 2));
    }

    public function testToStringMixedMap() {
        // nested array values are preserved
        $this->assertSame([ "k" => [ "x" => 1 ] ], Arrays::toStringMixedMap([ "k" => [ "x" => 1 ] ]));

        // scalar and string values are preserved
        $this->assertSame([ "k" => 1 ], Arrays::toStringMixedMap([ "k" => 1 ]));
        $this->assertSame([ "k" => "v" ], Arrays::toStringMixedMap([ "k" => "v" ]));

        // object values are preserved (same instance)
        $obj = (object)[ "x" => 1 ];
        $this->assertSame([ "k" => $obj ], Arrays::toStringMixedMap([ "k" => $obj ]));

        // non-array input returns empty map
        $this->assertSame([], Arrays::toStringMixedMap("x"));
    }

    public function testGetValues() {
        // associative arrays return their values in order
        $this->assertSame([ 1, 2 ], Arrays::getValues([ "a" => 1, "b" => 2 ]));

        // lists are returned unchanged
        $this->assertSame([ 1, 2, 3 ], Arrays::getValues([ 1, 2, 3 ]));

        // non-sequential numeric keys are reindexed
        $this->assertSame([ 10, 30 ], Arrays::getValues([ 0 => 10, 2 => 30 ]));

        // empty array returns empty list
        $this->assertSame([], Arrays::getValues([]));

        // object values are preserved (same instance)
        $obj = (object)[ "x" => 1 ];
        $this->assertSame([ $obj ], Arrays::getValues([ "a" => $obj ]));
    }

    public function testIsEmpty() {
        // empty array is empty
        $this->assertTrue(Arrays::isEmpty([]));

        // array with non-empty value is not empty
        $this->assertFalse(Arrays::isEmpty([ 1 ]));

        // array with empty string value is empty when checking that key
        $this->assertTrue(Arrays::isEmpty([ "a" => "" ], "a"));

        // null is empty
        $this->assertTrue(Arrays::isEmpty(null));

        // non-empty scalar is not empty
        $this->assertFalse(Arrays::isEmpty("x"));

        // empty string is empty
        $this->assertTrue(Arrays::isEmpty(""));

        // 0 is empty
        $this->assertTrue(Arrays::isEmpty(0));

        // false is empty
        $this->assertTrue(Arrays::isEmpty(false));

        // key lookup treats 0, false and missing keys as empty
        $this->assertTrue(Arrays::isEmpty([ "a" => 0 ], "a"));
        $this->assertTrue(Arrays::isEmpty([ "a" => false ], "a"));
        $this->assertTrue(Arrays::isEmpty([], "missing"));

        // array containing empty values is not considered empty as a whole
        $this->assertFalse(Arrays::isEmpty([ "a" => "" ]));
    }

    public function testLength() {
        $this->assertSame(3, Arrays::length([ 1, 2, 3 ]));

        // associative arrays count their keys
        $this->assertSame(2, Arrays::length([ "a" => 1, "b" => 2 ]));

        // empty array is zero length
        $this->assertSame(0, Arrays::length([]));

        // null is treated as empty
        $this->assertSame(0, Arrays::length(null));

        // nested arrays count only top-level entries
        $this->assertSame(2, Arrays::length([[ 1 ], [ 2 ]]));
    }

    public function testContains() {
        // simple values
        $this->assertTrue(Arrays::contains([[ "id" => 1 ], [ "id" => 2 ]], 2, "id"));
        $this->assertFalse(Arrays::contains([ 1, 2, 3 ], 4));
        $this->assertTrue(Arrays::contains([ "a", "b" ], [ "a", "b" ]));

        // works with single non-array values (toArray wraps them)
        $this->assertTrue(Arrays::contains("x", "x"));

        // works with objects as rows when checking a property
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        $this->assertTrue(Arrays::contains($rowsObj, 2, "id"));

        // when needle is an array and atLeastOne is true, any match suffices
        $this->assertTrue(Arrays::contains([[ "id" => 1 ], [ "id" => 2 ]], [ 2, 3 ], "id", true, true));

        // when needle is an array and atLeastOne is false, all values must be present
        $this->assertFalse(Arrays::contains([[ "id" => 1 ]], [ 1, 2 ], "id"));

        // case sensitivity behavior
        $this->assertTrue(Arrays::contains([ "A" ], "a"));
        $this->assertFalse(Arrays::contains([ "A" ], "a", null, false));

        // when needle is an array and all values are present (atLeastOne=false) -> true
        $this->assertTrue(Arrays::contains([
            [ "id" => 1 ], [ "id" => 2 ], [ "id" => 3 ]
        ], [ 2, 3 ], "id"));

        // when atLeastOne is true and none match -> false
        $this->assertFalse(Arrays::contains([
            [ "id" => 1 ]
        ], [ 2, 3 ], "id", true, true));
    }

    public function testContainsKey() {
        $this->assertTrue(Arrays::containsKey([ "a" => 1 ], "a"));
        $this->assertFalse(Arrays::containsKey([ "a" => 1 ], "b"));

        // numeric-string keys vs int keys
        $this->assertTrue(Arrays::containsKey([ "1" => "v" ], 1));
        $this->assertFalse(Arrays::containsKey([ 1 => "v" ], "1"));
        $this->assertTrue(Arrays::containsKey([ 1 => "v" ], 1));

        // empty array
        $this->assertFalse(Arrays::containsKey([], "x"));

        // float-like string key preserved
        $this->assertTrue(Arrays::containsKey([ "1.23" => "x" ], "1.23"));
    }

    public function testIsEqual() {
        $this->assertTrue(Arrays::isEqual([ 1, 2 ], [ 1, 2 ]));
        $this->assertFalse(Arrays::isEqual([ 1 ], [ 1, 2 ]));

        // different order matters when not using a key
        $this->assertFalse(Arrays::isEqual([ 1, 2 ], [ 2, 1 ]));

        // strict comparison (types) is used
        $this->assertFalse(Arrays::isEqual([ 1 ], [ "1" ]));

        // associative arrays with different keys are not equal even if values match
        $this->assertFalse(Arrays::isEqual([ "x" => 1 ], [ "y" => 1 ]));

        // nested arrays compared by a key: order-independent presence check
        $a = [[ "id" => 1 ], [ "id" => 2 ]];
        $b = [[ "id" => 2 ], [ "id" => 1 ]];
        $this->assertTrue(Arrays::isEqual($a, $b, "id"));

        // nested arrays missing the key cause inequality
        $this->assertFalse(Arrays::isEqual([[ "id" => 1 ], [ "no" => 2 ]], [[ "id" => 1 ], [ "id" => 2 ]], "id"));
    }

    public function testIsEqualWithKeys() {
        $a = [ "x" => 1, "y" => 2 ];
        $b = [ "x" => 1, "y" => 3 ];
        $this->assertTrue(Arrays::isEqualWithKeys($a, $a, [ "x", "y" ]));
        $this->assertFalse(Arrays::isEqualWithKeys($a, $b, [ "x", "y" ]));

        // order of keys doesn't matter for associative arrays
        $d = [ "y" => 2, "x" => 1 ];
        $this->assertTrue(Arrays::isEqualWithKeys($a, $d, [ "x", "y" ]));

        // missing keys cause inequality
        $e = [ "x" => 1 ];
        $this->assertFalse(Arrays::isEqualWithKeys($a, $e, [ "x", "y" ]));

        // extra keys are ignored when comparing selected keys
        $f = [ "x" => 1, "y" => 2, "z" => 3 ];
        $this->assertTrue(Arrays::isEqualWithKeys($a, $f, [ "x", "y" ]));

        // type differences are significant (strict)
        $c = [ "x" => 1, "y" => "2" ];
        $this->assertFalse(Arrays::isEqualWithKeys($a, $c, [ "x", "y" ]));
    }

    public function testIsEqualJSON() {
        $this->assertTrue(Arrays::isEqualJSON([ "a" => 1 ], (object)[ "a" => 1 ]));

        // array vs JSON string
        $this->assertTrue(Arrays::isEqualJSON([ "a" => 1 ], '{"a":1}'));

        // order matters for lists
        $this->assertFalse(Arrays::isEqualJSON([ 1, 2 ], [ 2, 1 ]));

        // different keys
        $this->assertFalse(Arrays::isEqualJSON([ "a" => 1 ], [ "b" => 1 ]));

        // null encodes to empty string in JSON::encode
        $this->assertTrue(Arrays::isEqualJSON(null, null));

        // nested structures and object vs string
        $this->assertTrue(Arrays::isEqualJSON((object)[ "x" => (object)[ "y" => 2 ] ], '{"x":{"y":2}}'));

        // invalid JSON string compared to array -> not equal
        $this->assertFalse(Arrays::isEqualJSON([ "a" => 1 ], '{invalid}'));
    }

    public function testIntersects() {
        // simple overlap
        $this->assertTrue(Arrays::intersects([ 1, 2 ], [ 2, 3 ]));

        // no overlap
        $this->assertFalse(Arrays::intersects([ 1 ], [ 2 ]));

        // empty arrays
        $this->assertFalse(Arrays::intersects([], []));
        $this->assertFalse(Arrays::intersects([], [ 1 ]));

        // identical arrays -> intersects
        $this->assertTrue(Arrays::intersects([ 1, 2 ], [ 1, 2 ]));

        // strict type comparison (1 !== "1")
        $this->assertFalse(Arrays::intersects([ 1 ], [ "1" ]));

        // nested arrays compared by value
        $this->assertTrue(Arrays::intersects([[ "a" => 1 ]], [[ "a" => 1 ]]));

        // objects require the same instance for ===
        $obj = (object)[ "x" => 1 ];
        $this->assertTrue(Arrays::intersects([ $obj ], [ $obj ]));
        $this->assertFalse(Arrays::intersects([ $obj ], [ (object)[ "x" => 1 ] ]));
    }

    public function testGetDiff() {
        $a = [[ "id" => 1 ], [ "id" => 2 ]];
        $b = [[ "id" => 2 ]];
        $this->assertSame([[ "id" => 1 ]], Arrays::getDiff($a, $b, "id"));
        $this->assertSame([ 1 ], Arrays::getDiff($a, $b, "id", "id"));

        // identical arrays -> empty diff
        $this->assertSame([], Arrays::getDiff($a, $a, "id"));

        // other empty -> all elements returned
        $this->assertSame($a, Arrays::getDiff($a, [], "id"));
        $this->assertSame([ 1, 2 ], Arrays::getDiff($a, [], "id", "id"));

        // rows that are not arrays are ignored
        $mixed = [ [ "id" => 1 ], 5, "x" ];
        $this->assertSame([[ "id" => 1 ]], Arrays::getDiff($mixed, [], "id"));

        // missing checkKey in a row causes it to be included
        $withMissing = [[ "id" => 1 ], [ "no" => 3 ]];
        $this->assertSame([[ "no" => 3 ]], Arrays::getDiff($withMissing, [[ "id" => 1 ]], "id"));
    }

    public function testAddFirst() {
        // add a single element to the start
        $this->assertSame([ 0, 1, 2 ], Arrays::addFirst([ 1, 2 ], 0));

        // add multiple elements using variadic args
        $this->assertSame([ 1, 2, 3 ], Arrays::addFirst([ 3 ], 1, 2));

        // add to an empty array
        $this->assertSame([ "a" ], Arrays::addFirst([], "a"));

        // objects are preserved (same instance returned)
        $o = (object)[ "x" => 1 ];
        $this->assertSame([ $o ], Arrays::addFirst([], $o));
    }

    public function testAddAt() {
        // insert single element in the middle
        $this->assertSame([ 1, 99, 2 ], Arrays::addAt([ 1, 2 ], 1, 99));

        // insert at the start
        $this->assertSame([ 0, 1, 2 ], Arrays::addAt([ 1, 2 ], 0, 0));

        // insert multiple elements (variadic)
        $this->assertSame([ 1, 2, 3, 4 ], Arrays::addAt([ 1, 4 ], 1, 2, 3));

        // insert with negative index (before last)
        $this->assertSame([ 1, 2, 9, 3 ], Arrays::addAt([ 1, 2, 3 ], -1, 9));

        // insert beyond end appends
        $this->assertSame([ 1, 2 ], Arrays::addAt([ 1 ], 10, 2));

        // objects are preserved (same instance)
        $o = (object)[ "x" => 1 ];
        $this->assertSame([ $o ], Arrays::addAt([], 0, $o));
    }

    public function testRemoveFirst() {
        // simple removal
        $this->assertSame([ 2, 3 ], Arrays::removeFirst([ 1, 2, 3 ]));

        // single-element becomes empty
        $this->assertSame([], Arrays::removeFirst([ 1 ]));

        // removing from an empty array returns empty array
        $this->assertSame([], Arrays::removeFirst([]));

        // objects preserved and first removed
        $o1 = (object)[ 'x' => 1 ];
        $o2 = (object)[ 'x' => 2 ];
        $this->assertSame([ $o2 ], Arrays::removeFirst([ $o1, $o2 ]));
    }

    public function testRemoveLast() {
        // remove last from a simple list
        $this->assertSame([ 1, 2 ], Arrays::removeLast([ 1, 2, 3 ]));

        // single-element becomes empty
        $this->assertSame([], Arrays::removeLast([ 1 ]));

        // removing from an empty array returns empty array
        $this->assertSame([], Arrays::removeLast([]));

        // objects preserved and last removed
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o1 ], Arrays::removeLast([ $o1, $o2 ]));

        // associative arrays keep remaining keys
        $this->assertSame([ "a" => 1 ], Arrays::removeLast([ "a" => 1, "b" => 2 ]));
    }

    public function testRemoveAt() {
        // remove middle element
        $this->assertSame([ 1, 3 ], Arrays::removeAt([ 1, 2, 3 ], 1));

        // remove at start
        $this->assertSame([ 2, 3 ], Arrays::removeAt([ 1, 2, 3 ], 0));

        // remove at end using negative index
        $this->assertSame([ 1, 2 ], Arrays::removeAt([ 1, 2, 3 ], -1));

        // single-element becomes empty
        $this->assertSame([], Arrays::removeAt([ 1 ], 0));

        // position beyond end does nothing
        $this->assertSame([ 1 ], Arrays::removeAt([ 1 ], 10));

        // objects preserved and element removed
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o1 ], Arrays::removeAt([ $o1, $o2 ], 1));
    }

    public function testRemoveValue() {
        // remove by key from nested rows
        $this->assertSame([[ "id" => 2 ]], Arrays::removeValue([[ "id" => 1 ], [ "id" => 2 ]], 1, "id"));

        // remove primitive values from a simple list
        $this->assertSame([ 2, 3 ], Arrays::removeValue([ 1, 2, 3 ], 1));

        // removing non-existing value leaves array unchanged
        $this->assertSame([ 1, 2 ], Arrays::removeValue([ 1, 2 ], 3));

        // removes all occurrences of the value
        $this->assertSame([ 2 ], Arrays::removeValue([ 1, 2, 1 ], 1));

        // works with objects (same instance preserved)
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        $this->assertSame([ $o2 ], Arrays::removeValue([ $o1, $o2 ], 1, "id"));

        // rows missing the idKey are not kept
        $withMissing = [[ "id" => 1 ], [ "no" => 3 ]];
        $this->assertSame([], Arrays::removeValue($withMissing, 1, "id"));

        // strict type comparison: string "1" is not equal to int 1
        $this->assertSame([ "1", "2" ], Arrays::removeValue([ "1", "2" ], 1));
    }

    public function testRemoveEmpty() {
        // basic removal of empty values
        $this->assertSame([ 1, "a" ], Arrays::removeEmpty([ 1, "", null, "a" ]));

        // empty input stays empty
        $this->assertSame([], Arrays::removeEmpty([]));

        // removes 0, false, empty string and null (empty() semantics)
        $this->assertSame([ 1, "a" ], Arrays::removeEmpty([ 0, 1, "", null, false, "a", "0" ]));

        // removes empty nested arrays but preserves non-empty arrays and objects
        $o1 = (object)[];
        $o2 = (object)[ "x" => 1 ];
        $this->assertSame([[ 1 ], $o1, $o2], Arrays::removeEmpty([ [], [ 1 ], $o1, $o2 ]));
    }

    public function testRemoveDuplicates() {
        // basic numeric duplicates
        $this->assertSame([ 1, 2 ], Arrays::removeDuplicates([ 1, 2, 1, 2 ]));

        // string duplicates
        $this->assertSame([ "a", "b" ], Arrays::removeDuplicates([ "a", "b", "a" ]));

        // strict types: int and string are not distinct
        $this->assertSame([ 1 ], Arrays::removeDuplicates([ 1, "1", 1 ]));

        // empty input remains empty
        $this->assertSame([], Arrays::removeDuplicates([]));
    }

    public function testMerge() {
        // simple associative merge
        $this->assertSame([ "a" => 1, "b" => 2 ], Arrays::merge([ "a" => 1 ], [ "b" => 2 ]));

        // key conflict: later array wins
        $this->assertSame([ "a" => 2 ], Arrays::merge([ "a" => 1 ], [ "a" => 2 ]));

        // numeric keys are reindexed (list merge)
        $this->assertSame([ 1, 2, 3 ], Arrays::merge([ 1, 2 ], [ 3 ]));

        // nested arrays are replaced, not deep-merged
        $this->assertSame([ "x" => [ "z" => 2 ] ], Arrays::merge([ "x" => [ "y" => 1 ] ], [ "x" => [ "z" => 2 ] ]));

        // object values preserved (same instance)
        $o = (object)[ "k" => "v" ];
        $this->assertSame([ "k" => $o ], Arrays::merge([ "k" => $o ], []));

        // merging with empty arrays returns the other
        $this->assertSame([ "a" => 1 ], Arrays::merge([], [ "a" => 1 ]));
    }

    public function testMergeLists() {
        // simple concatenation
        $this->assertSame([ 1, 2, 3 ], Arrays::mergeLists([ 1, 2 ], [ 3 ]));

        // merging with empty list returns other list
        $this->assertSame([ 1 ], Arrays::mergeLists([], [ 1 ]));

        // duplicates are preserved and reindexed
        $this->assertSame([ 1, 2, 2, 3 ], Arrays::mergeLists([ 1, 2 ], [ 2, 3 ]));

        // variadic merging of multiple lists
        $this->assertSame([ 1, 2, 3 ], Arrays::mergeLists([ 1 ], [ 2 ], [ 3 ]));

        // object elements preserve identity
        $o = (object)[ "x" => 1 ];
        $this->assertSame([ $o ], Arrays::mergeLists([], [ $o ]));
    }

    public function testSlice() {
        // basic slice with explicit amount
        $this->assertSame([ 2, 3 ], Arrays::slice([ 1, 2, 3 ], 1, 2));

        // slice from index to end when amount omitted
        $this->assertSame([ 3 ], Arrays::slice([ 1, 2, 3 ], 2));

        // amount larger than remaining returns remaining elements
        $this->assertSame([ 2, 3 ], Arrays::slice([ 1, 2, 3 ], 1, 10));

        // negative from counts from the end
        $this->assertSame([ 2 ], Arrays::slice([ 1, 2, 3 ], -2, 1));

        // empty input returns empty
        $this->assertSame([], Arrays::slice([], 0, 2));

        // objects preserved (same instance)
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o2 ], Arrays::slice([ $o1, $o2 ], 1, 1));
    }

    public function testPaginate() {
        $this->assertSame([ 1, 2 ], Arrays::paginate([ 1, 2, 3 ], 0, 2));
        $this->assertSame([ 3, 4 ], Arrays::paginate([ 1, 2, 3, 4 ], 1, 2));

        // page beyond available items returns empty list
        $this->assertSame([], Arrays::paginate([ 1, 2, 3 ], 2, 2));

        // amount larger than remaining returns an empty array
        $this->assertSame([], Arrays::paginate([ 1, 2, 3 ], 1, 5));

        // empty input always returns empty
        $this->assertSame([], Arrays::paginate([], 0, 3));

        // object identity preserved when paginating
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o2 ], Arrays::paginate([ $o1, $o2 ], 1, 1));
    }

    public function testSubArray() {
        // basic intersection by value
        $this->assertSame([ 2 ], Arrays::subArray([ 1, 2, 3 ], [ 2, 4 ]));

        // no matches -> empty
        $this->assertSame([], Arrays::subArray([ 1, 2, 3 ], [ 4, 5 ]));

        // it does not preserve duplicates present in the source
        $this->assertSame([ 2 ], Arrays::subArray([ 1, 2, 2, 3 ], [ 2 ]));

        // empty source returns empty
        $this->assertSame([], Arrays::subArray([], [ 1, 2 ]));

        // empty selector returns empty
        $this->assertSame([], Arrays::subArray([ 1, 2 ], []));

        // object identity preserved
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        $this->assertSame([ $o2 ], Arrays::subArray([ $o1, $o2 ], [ $o2 ]));
    }

    public function testExtend() {
        $a = [ "x" => [ "y" => 1 ] ];
        $b = [ "x" => [ "z" => 2 ] ];
        $this->assertSame([ "x" => [ "y" => 1, "z" => 2 ] ], Arrays::extend($a, $b));

        // adds new top-level keys
        $a2 = [ "a" => 1 ];
        $b2 = [ "b" => 2 ];
        $this->assertSame([ "a" => 1, "b" => 2 ], Arrays::extend($a2, $b2));

        // scalar keys are overridden by the second array
        $a3 = [ "k" => 1 ];
        $b3 = [ "k" => 2 ];
        $this->assertSame([ "k" => 2 ], Arrays::extend($a3, $b3));

        // extending with an empty array returns the first
        $this->assertSame($a2, Arrays::extend($a2, []));

        // object values preserve identity
        $obj = (object)[ "x" => 1 ];
        $res = Arrays::extend([ "o" => $obj ], []);
        $this->assertSame([ "o" => $obj ], $res);
    }

    public function testSort() {
        // simple ascending
        $this->assertSame([ 1, 2, 3 ], Arrays::sort([ 3, 1, 2 ]));

        // descending using comparator
        $this->assertSame([ 3, 2, 1 ], Arrays::sort([ 1, 2, 3 ], fn($a, $b) => $b <=> $a));

        // associative arrays with callback preserve keys
        $assoc = [ "c" => 3, "a" => 1, "b" => 2 ];
        $sortedAssoc = Arrays::sort($assoc, fn($x, $y) => $x <=> $y);
        $this->assertSame([ "a" => 1, "b" => 2, "c" => 3 ], $sortedAssoc);

        // sorting list of objects by property (same instances preserved)
        $o1 = (object)[ "v" => 2 ];
        $o2 = (object)[ "v" => 1 ];
        $sorted = Arrays::sort([ $o1, $o2 ], fn($x, $y) => $x->v <=> $y->v);
        $this->assertSame([ $o2, $o1 ], $sorted);
    }

    public function testSortList() {
        $this->assertSame([ 1, 2 ], Arrays::sortList([ 2, 1 ]));

        // empty list remains empty
        $this->assertSame([], Arrays::sortList([]));

        // duplicates preserved and reindexed
        $this->assertSame([ 1, 2, 2, 3 ], Arrays::sortList([ 2, 3, 1, 2 ]));

        // custom comparator (descending)
        $this->assertSame([ 3, 2, 1 ], Arrays::sortList([ 1, 2, 3 ], fn($a, $b) => $b <=> $a));

        // sorting objects by property preserves instances
        $o1 = (object)[ "v" => 2 ];
        $o2 = (object)[ "v" => 1 ];
        $sorted = Arrays::sortList([ $o1, $o2 ], fn($x, $y) => $x->v <=> $y->v);
        $this->assertSame([ $o2, $o1 ], $sorted);
    }

    public function testReverse() {
        $this->assertSame([ 3, 2, 1 ], Arrays::reverse([ 1, 2, 3 ]));

        // empty list remains empty
        $this->assertSame([], Arrays::reverse([]));

        // single-element unchanged
        $this->assertSame([ 1 ], Arrays::reverse([ 1 ]));

        // object instances preserved and order reversed
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o2, $o1 ], Arrays::reverse([ $o1, $o2 ]));
    }

    public function testMap() {
        $this->assertSame([ 2, 4 ], Arrays::map([ 1, 2 ], fn($v) => $v * 2));

        // empty input returns empty
        $this->assertSame([], Arrays::map([], fn($v) => $v * 2));

        // associative arrays preserve keys
        $this->assertSame([ "a" => 2, "b" => 4 ], Arrays::map([ "a" => 1, "b" => 2 ], fn($v) => $v * 2));

        // mapping can return different types
        $this->assertSame([ "1", "2" ], Arrays::map([ 1, 2 ], fn($v) => (string)$v));

        // object instances preserved when callback returns the same instance
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        $this->assertSame([ $o1, $o2 ], Arrays::map([ $o1, $o2 ], fn($v) => $v));
    }

    public function testRandom() {
        // random from a simple list
        $val = Arrays::random([ 10, 20, 30 ]);
        $this->assertContains($val, [ 10, 20, 30 ]);

        // random from an associative array returns one of the values
        $assoc = [ "a" => 100, "b" => 200 ];
        $this->assertContains(Arrays::random($assoc), [ 100, 200 ]);

        // single-element array returns that element
        $this->assertSame(42, Arrays::random([ 42 ]));

        // empty array returns null
        $this->assertNull(Arrays::random([]));

        // non-consecutive numeric keys
        $mixed = [ 5 => "x", 999 => "y" ];
        $this->assertContains(Arrays::random($mixed), [ "x", "y" ]);

        // objects are returned as-is (same instance)
        $o1 = (object)[ "a" => 1 ];
        $o2 = (object)[ "b" => 2 ];
        $this->assertContains(Arrays::random([ $o1, $o2 ]), [ $o1, $o2 ]);
    }

    public function testMax() {
        $this->assertSame(5, Arrays::max([ 1, 5, 3 ]));

        // empty arrays return 0
        $this->assertSame(0, Arrays::max([]));

        // works with negative numbers
        $this->assertSame(-1, Arrays::max([ -3, -1, -2 ]));

        // associative arrays consider values
        $this->assertSame(7, Arrays::max([ "a" => 7, "b" => 2 ]));

        // single-element arrays return that element
        $this->assertSame(4, Arrays::max([ 4 ]));

        // null treated as empty
        $this->assertSame(0, Arrays::max(null));
    }

    public function testSum() {
        $this->assertSame(6, Arrays::sum([ 1, 2, 3 ]));
        $this->assertSame(3, Arrays::sum([[ "v" => 1 ], [ "v" => 2 ]], "v"));

        // empty input -> zero
        $this->assertSame(0, Arrays::sum([]));

        // negative values
        $this->assertSame(1, Arrays::sum([ -1, 2 ]));

        // floats preserved
        $this->assertSame(3.75, Arrays::sum([ 1.5, 2.25 ]));

        // numeric strings converted
        $this->assertSame(3.0, Arrays::sum([ "1", "2" ]));

        // when summing by key, missing keys are ignored
        $this->assertSame(1, Arrays::sum([[ "v" => 1 ], [ "x" => 2 ]], "v"));
    }

    public function testAverage() {
        $this->assertSame(2.0, Arrays::average([ 1, 2, 3 ]));
        $this->assertSame(4.0, Arrays::average([ 4 ]));

        // negative numbers
        $this->assertSame(0.0, Arrays::average([ -1, 1 ]));

        // empty input -> zero
        $this->assertSame(0.0, Arrays::average([]));

        // numeric strings are converted
        $this->assertSame(2.0, Arrays::average([ "1", "2", "3" ]));

        // decimals parameter rounds the result
        $this->assertSame(1.5, Arrays::average([ 1.2, 1.8 ], 1));

        // associative arrays count keys
        $this->assertSame(2.0, Arrays::average([ "a" => 1, "b" => 3 ]));

        // averaging by key divides by total length (missing keys reduce average)
        $this->assertSame(0.5, Arrays::average([[ "v" => 1 ], [ "x" => 2 ]], 1, "v"));

        // average by key with all present
        $this->assertSame(1.5, Arrays::average([[ "v" => 1 ], [ "v" => 2 ] ], 1, "v"));
    }

    public function testCreateMap() {
        $rows = [[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]];
        $map = Arrays::createMap($rows, "id");
        $this->assertArrayHasKey(1, $map);
        $this->assertArrayHasKey(2, $map);
        $this->assertSame($rows[0], $map[1]);

        // later rows override earlier rows with the same key
        $dup = [ [ "id" => 1, "v" => "a" ], [ "id" => 1, "v" => "c" ] ];
        $mapDup = Arrays::createMap($dup, "id");
        $this->assertSame("c", $mapDup[1]["v"]);

        // rows missing the key are ignored
        $mixed = [[ "no" => 1 ], [ "id" => 2, "v" => "b" ]];
        $mapMixed = Arrays::createMap($mixed, "id");
        $this->assertArrayHasKey(2, $mapMixed);
        $this->assertCount(1, $mapMixed);

        // works with objects (preserves same instance)
        $o1 = (object)[ "id" => 1, "v" => "a" ];
        $o2 = (object)[ "id" => 2, "v" => "b" ];
        $mapObj = Arrays::createMap([ $o1, $o2 ], "id");
        $this->assertSame($o1, $mapObj[1]);
    }

    public function testCreateArray() {
        $rows = [[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]];
        $this->assertSame([ 1, 2 ], Arrays::createArray($rows, "id"));

        // duplicates preserved by default
        $dup = [[ "id" => 1 ], [ "id" => 1 ]];
        $this->assertSame([ 1, 1 ], Arrays::createArray($dup, "id"));

        // distinct removes duplicates
        $this->assertSame([ 1 ], Arrays::createArray($dup, "id", false, true));

        // multiple-key extraction returns concatenated values
        $multi = [[ "a" => 1, "b" => 2 ], [ "a" => 3, "b" => 4 ]];
        $this->assertSame([ "1 - 2", "3 - 4" ], Arrays::createArray($multi, [ "a", "b" ]));

        // works with object rows
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        $this->assertSame([ 1, 2 ], Arrays::createArray([ $o1, $o2 ], "id"));

        // empty input returns empty
        $this->assertSame([], Arrays::createArray([], "id"));

        // null key returns the rows themselves
        $rows2 = [[ "id" => 5 ], [ "id" => 6 ]];
        $this->assertSame($rows2, Arrays::createArray($rows2, null));
    }

    public function testGetFirst() {
        $this->assertSame(1, Arrays::getFirst([ 1, 2, 3 ]));
        $this->assertSame("a", Arrays::getFirst([ [ "x" => "a" ] ], "x"));

        // empty input returns null
        $this->assertNull(Arrays::getFirst([]));

        // associative arrays return the first value
        $this->assertSame(1, Arrays::getFirst([ "a" => 1, "b" => 2 ]));

        // object rows preserved when no key provided
        $obj = (object)[ "v" => "z" ];
        $this->assertSame($obj, Arrays::getFirst([ $obj ]));

        // missing key on the first row returns empty string (getValue default)
        $this->assertSame("", Arrays::getFirst([[ "a" => 1 ]], "missing"));
    }

    public function testGetFirstKey() {
        // associative arrays return the first key (string preserved)
        $this->assertSame("a", Arrays::getFirstKey([ "a" => 1, "b" => 2 ]));

        // empty array returns null
        $this->assertNull(Arrays::getFirstKey([]));

        // numeric list returns numeric 0 as first key
        $this->assertSame(0, Arrays::getFirstKey([ 10, 20 ]));

        // non-consecutive numeric keys preserved
        $this->assertSame(5, Arrays::getFirstKey([ 5 => "x", 10 => "y" ]));

        // numeric-string key is converted to int by PHP array keys, so "1" becomes 1
        $this->assertSame(1, Arrays::getFirstKey([ "1" => "v" ]));
    }

    public function testGetLast() {
        $this->assertSame(3, Arrays::getLast([ 1, 2, 3 ]));

        // associative arrays return the last value
        $this->assertSame(2, Arrays::getLast([ "a" => 1, "b" => 2 ]));

        // empty array returns null
        $this->assertNull(Arrays::getLast([]));

        // non-consecutive numeric keys preserved
        $this->assertSame("y", Arrays::getLast([ 5 => "x", 10 => "y" ]));

        // works with rows and key extraction
        $rows = [[ "x" => "a" ], [ "x" => "b" ]];
        $this->assertSame("b", Arrays::getLast($rows, "x"));

        // missing key on last row returns empty string
        $this->assertSame("", Arrays::getLast([[ "a" => 1 ], [ "b" => 2 ] ], "missing"));
    }

    public function testGetIndex() {
        // simple value found
        $this->assertSame(1, Arrays::getIndex([ 1, 2, 3 ], 2));

        // not found returns -1
        $this->assertSame(-1, Arrays::getIndex([ 1 ], 5));

        // duplicates -> first index returned
        $this->assertSame(1, Arrays::getIndex([ 1, 2, 2 ], 2));

        // empty array -> -1
        $this->assertSame(-1, Arrays::getIndex([], "x"));

        // case sensitive by default (no match)
        $this->assertSame(-1, Arrays::getIndex([ "A" ], "a"));

        // case-insensitive search when flag enabled
        $this->assertSame(0, Arrays::getIndex([ "A" ], "a", true));
    }

    public function testFindIndex() {
        // simple found at first index
        $this->assertSame(0, Arrays::findIndex([ [ "id" => 1 ] ], "id", 1));

        // not found on empty input
        $this->assertSame(-1, Arrays::findIndex([ ], "id", 1));

        // found at second position
        $rows = [[ "id" => 9 ], [ "id" => 10 ]];
        $this->assertSame(1, Arrays::findIndex($rows, "id", 10));

        // works with object rows
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        $this->assertSame(1, Arrays::findIndex($rowsObj, "id", 2));

        // missing key returns -1
        $this->assertSame(-1, Arrays::findIndex([[ "no" => 1 ]], "id", 1));

        // strict comparison: string vs int does not match
        $this->assertSame(-1, Arrays::findIndex([[ "id" => "1" ]], "id", 1));

        // associative array returns the original key
        $assoc = [ "a" => [ "id" => 1 ], "b" => [ "id" => 2 ] ];
        $this->assertSame("b", Arrays::findIndex($assoc, "id", 2));
    }

    public function testHasValue() {
        // simple present
        $this->assertTrue(Arrays::hasValue([[ "id" => 1 ]], "id", 1));

        // empty input -> false
        $this->assertFalse(Arrays::hasValue([], "id", 1));

        // works with object rows
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        $this->assertTrue(Arrays::hasValue($rowsObj, "id", 2));

        // missing key -> false
        $this->assertFalse(Arrays::hasValue([[ "no" => 1 ]], "id", 1));

        // strict comparison: string vs int does not match
        $this->assertFalse(Arrays::hasValue([[ "id" => "1" ]], "id", 1));

        // duplicates handled (presence detected)
        $this->assertTrue(Arrays::hasValue([[ "id" => 2 ], [ "id" => 2 ]], "id", 2));
    }

    public function testFindValue() {
        $rows = [[ "id" => 1, "x" => 9 ], [ "id" => 2, "x" => 8 ]];
        $this->assertSame($rows[0], Arrays::findValue($rows, "id", 1));

        // works with object rows
        $rowsObj = [(object)[ "id" => 1, "x" => 9 ], (object)[ "id" => 2, "x" => 8 ]];
        $this->assertSame($rowsObj[1], Arrays::findValue($rowsObj, "id", 2));

        // empty or missing key -> null
        $this->assertNull(Arrays::findValue([], "id", 1));
        $this->assertNull(Arrays::findValue([[ "no" => 1 ]], "id", 1));

        // strict comparison: string vs int does not match
        $this->assertNull(Arrays::findValue([[ "id" => "1" ]], "id", 1));

        // multiple matches -> first row returned
        $many = [[ "id" => 2, "x" => 1 ], [ "id" => 2, "x" => 2 ]];
        $this->assertSame($many[0], Arrays::findValue($many, "id", 2));

        // associative arrays: returns the matching value (row)
        $assoc = [ "a" => [ "id" => 1, "x" => 9 ], "b" => [ "id" => 2, "x" => 8 ] ];
        $this->assertSame($assoc["b"], Arrays::findValue($assoc, "id", 2));
    }

    public function testFindValues() {
        $rows = [[ "id" => 1 ], [ "id" => 1 ], [ "id" => 2 ]];
        $this->assertCount(2, Arrays::findValues($rows, "id", 1));

        // works with object rows and preserves instances
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ], (object)[ "id" => 1 ]];
        $resObj = Arrays::findValues($rowsObj, "id", 1);
        $this->assertCount(2, $resObj);
        $this->assertSame($rowsObj[0], $resObj[0]);
        $this->assertSame($rowsObj[2], $resObj[1]);

        // empty input -> empty array
        $this->assertSame([], Arrays::findValues([], "id", 1));

        // rows missing the key are ignored
        $withMissing = [[ "no" => 1 ], [ "id" => 1 ]];
        $resMissing = Arrays::findValues($withMissing, "id", 1);
        $this->assertCount(1, $resMissing);
        $this->assertSame([ [ "id" => 1 ] ], $resMissing);

        // strict comparison: string vs int does not match
        $this->assertCount(0, Arrays::findValues([[ "id" => "1" ]], "id", 1));

        // associative input returns matching values as a simple list
        $assoc = [ "a" => [ "id" => 1 ], "b" => [ "id" => 1 ] ];
        $resAssoc = Arrays::findValues($assoc, "id", 1);
        $this->assertCount(2, $resAssoc);
        $this->assertSame($assoc["a"], $resAssoc[0]);
        $this->assertSame($assoc["b"], $resAssoc[1]);
    }

    public function testGetKey() {
        $this->assertSame("name", Arrays::getKey("name"));
        $this->assertSame("PrefName", Arrays::getKey("name", "Pref"));

        // preserves case of provided key's first letter when already uppercase
        $this->assertSame("PreName", Arrays::getKey("Name", "Pre"));

        // lowercase prefix concatenated with ucfirst(key)
        $this->assertSame("preX", Arrays::getKey("x", "pre"));

        // empty prefix returns the key unchanged
        $this->assertSame("k", Arrays::getKey("k", ""));
    }

    public function testGetValue() {
        // when key is empty, return the array/scalar unchanged
        $row = [ "a" => [ "b" => "c" ]];
        $this->assertSame($row, Arrays::getValue($row, ""));
        $this->assertSame("scalar", Arrays::getValue("scalar", ""));

        // single key lookup: arrays and objects
        $this->assertSame("v", Arrays::getValue([ "k" => "v" ], "k"));
        $obj = (object)[ "k" => "v" ];
        $this->assertSame("v", Arrays::getValue($obj, "k"));

        // missing single key returns provided default
        $this->assertSame("D", Arrays::getValue([ "a" => 1 ], "x", " - ", "", false, "D"));

        // empty value is treated as missing unless useEmpty is true
        $emptyRow = [ "k" => "" ];
        $this->assertSame("X", Arrays::getValue($emptyRow, "k", " - ", "", false, "X"));
        $this->assertSame("", Arrays::getValue($emptyRow, "k", " - ", "", true, "X"));

        // numeric-string key is considered empty by empty() semantics, so a "0" key
        // causes getValue to treat the key as empty and return the original array
        $this->assertSame([ 0 => "zero" ], Arrays::getValue([ 0 => "zero" ], "0"));

        // multiple keys: concatenation with default glue
        $this->assertSame("1 - 2", Arrays::getValue([ "a" => 1, "b" => 2 ], [ "a", "b" ]));

        // custom glue
        $this->assertSame("1, 2", Arrays::getValue([ "a" => 1, "b" => 2 ], [ "a", "b" ], ", "));

        // prefix is prepended using ucfirst on the key
        $prefixed = [ "preName" => "pv" ];
        $this->assertSame("pv", Arrays::getValue($prefixed, "name", " - ", "pre"));

        // prefix with multiple keys and custom glue
        $multiPref = [ "preA" => 1, "preB" => 2 ];
        $this->assertSame("1 + 2", Arrays::getValue($multiPref, [ "a", "b" ], " + ", "pre"));

        // array-of-keys where none exist -> empty string (default not applied for arrays)
        $this->assertSame("", Arrays::getValue([ "a" => 1 ], [ "x", "y" ], " - ", "", false, "D"));

        // object with multiple keys
        $objMulti = (object)[ "name" => "n", "age" => 10 ];
        $this->assertSame("n - 10", Arrays::getValue($objMulti, [ "name", "age" ]));
    }

    public function testGetOneValue() {
        // simple array key
        $this->assertSame("v", Arrays::getOneValue([ "k" => "v" ], "k"));

        // object property
        $obj = (object)[ "k" => "v" ];
        $this->assertSame("v", Arrays::getOneValue($obj, "k"));

        // missing key returns default null
        $this->assertNull(Arrays::getOneValue([ ], "x"));

        // empty string treated as empty unless useEmpty=true
        $empty = [ "k" => "" ];
        $this->assertNull(Arrays::getOneValue($empty, "k"));
        $this->assertSame("", Arrays::getOneValue($empty, "k", true));

        // numeric 0 value is empty by default but preserved when useEmpty=true
        $zeroVal = [ "k" => 0 ];
        $this->assertNull(Arrays::getOneValue($zeroVal, "k"));
        $this->assertSame(0, Arrays::getOneValue($zeroVal, "k", true));

        // default parameter is returned when key missing
        $this->assertSame("D", Arrays::getOneValue([ ], "x", false, "D"));

        // numeric-string key accesses numeric keys correctly
        $num = [ 0 => "zero" ];
        $this->assertSame("zero", Arrays::getOneValue($num, "0"));
    }
}
