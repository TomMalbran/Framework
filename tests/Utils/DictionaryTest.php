<?php
namespace Tests\Utils;

use Framework\Utils\Dictionary;
use Framework\Date\Date;

use PHPUnit\Framework\TestCase;
use stdClass;

class DictionaryTest extends TestCase {

    public function testConstruct() {
        $d1 = new Dictionary([ "a" => 1, "b" => 2 ]);
        $this->assertIsArray($d1->toArray());
        $this->assertEquals(1, $d1->get("a"));

        // from object
        $obj = new stdClass();
        $obj->x = 10;
        $obj->y = "z";
        $d2 = new Dictionary($obj);
        $this->assertEquals(10, $d2->get("x"));

        // from encoded JSON string
        $json = json_encode(["m" => 5, "n" => "v"]);
        $d3 = new Dictionary($json);
        $this->assertEquals(5, $d3->get("m"));

        // from another Dictionary
        $orig = new Dictionary([ "k" => "val" ]);
        $d4 = new Dictionary($orig);
        $this->assertEquals("val", $d4->get("k"));

        // invalid inputs should create an empty Dictionary
        $d5 = new Dictionary(12345);
        $this->assertTrue($d5->isEmpty());

        $d6 = new Dictionary("not a json string");
        $this->assertTrue($d6->isEmpty());
    }

    public function testIsEmpty() {
        $d1 = new Dictionary();
        $this->assertTrue($d1->isEmpty());

        $d2 = new Dictionary([ "a" => 1 ]);
        $this->assertFalse($d2->isEmpty());
    }

    public function testIsNotEmpty() {
        $d1 = new Dictionary();
        $this->assertFalse($d1->isNotEmpty());

        $d2 = new Dictionary([ "a" => 1 ]);
        $this->assertTrue($d2->isNotEmpty());
    }

    public function testGetTotal() {
        // total should count top-level keys, not nested ones
        $d1 = new Dictionary([ "a" => 1, "b" => 2 ]);
        $this->assertEquals(2, $d1->getTotal());

        // total should count top-level items in a list, not nested keys
        $d2 = new Dictionary([[ "id" => "x" ], [ "id" => "y" ]]);
        $this->assertEquals(2, $d2->getTotal());

        // invalid input should return 0
        $d3 = new Dictionary("not json");
        $this->assertEquals(0, $d3->getTotal());

        // empty Dictionary should return 0
        $dEmpty = new Dictionary();
        $this->assertEquals(0, $dEmpty->getTotal());
    }

    public function testIsEqual() {
        $d1 = new Dictionary([ "k" => "v" ]);
        $d2 = new Dictionary([ "k" => "v" ]);
        $this->assertTrue($d1->isEqual($d2));

        $d3 = new Dictionary([ "k" => "x" ]);
        $this->assertFalse($d1->isEqual($d3));
    }

    public function testIsNotEqual() {
        $d1 = new Dictionary([ "k" => "v" ]);
        $d2 = new Dictionary([ "k" => "x" ]);
        $this->assertTrue($d1->isNotEqual($d2));

        $d3 = new Dictionary([ "k" => "v" ]);
        $this->assertFalse($d1->isNotEqual($d3));
    }

    public function testIsList() {
        // a list is an array with numeric keys starting from 0
        $d1 = new Dictionary([ 1, 2, 3 ]);
        $this->assertTrue($d1->isList());

        // an associative array can have a member that is a list
        $d2 = new Dictionary([ "list" => [ "a", "b" ]]);
        $this->assertTrue($d2->isList("list"));

        // non-list arrays should return false
        $d3 = new Dictionary([ "a" => "b" ]);
        $this->assertFalse($d3->isList());

        // invalid input should return false
        $d4 = new Dictionary("not json");
        $this->assertFalse($d4->isList());
    }

    public function testIsArrayList() {
        // an array of arrays with numeric keys is an array list
        $d1 = new Dictionary([[ "a" => 1 ], [ "a" => 2 ]]);
        $this->assertTrue($d1->isArrayList());

        // an array of arrays with numeric keys can be nested
        $d2 = new Dictionary([ "key" => [[ "x" => 1 ]] ]);
        $this->assertTrue($d2->isArrayList("key"));

        // an array with non-numeric keys should return false
        $d3 = new Dictionary([ "a" => [ "x" => 1 ] ]);
        $this->assertFalse($d3->isArrayList("a"));

        // invalid input should return false
        $d4 = new Dictionary("not json");
        $this->assertFalse($d4->isArrayList());
    }

    public function testHas() {
        $d1 = new Dictionary([ "a" => 1 ]);
        $this->assertTrue($d1->has("a"));
        $this->assertFalse($d1->has("missing"));

        // keys can also be numeric
        $d2 = new Dictionary([ "x", "y" ]);
        $this->assertTrue($d2->has(0));
        $this->assertFalse($d2->has(2));

        // the value can be 0 or empty string but the key still exists
        $d3 = new Dictionary([ "k" => 0, "k2" => "" ]);
        $this->assertTrue($d3->has("k"));
        $this->assertTrue($d3->has("k2"));

        // invalid input should return false
        $d4 = new Dictionary("not json");
        $this->assertFalse($d4->has("any"));
    }

    public function testHasValue() {
        $d = new Dictionary([ "a" => "", "b" => "x", "c" => 0 ]);
        $this->assertFalse($d->hasValue("a"));
        $this->assertTrue($d->hasValue("b"));
        $this->assertFalse($d->hasValue("c"));
        $this->assertFalse($d->hasValue("missing"));

        // keys can also be numeric
        $d2 = new Dictionary([ "", "y", 0 ]);
        $this->assertFalse($d2->hasValue(0));
        $this->assertTrue($d2->hasValue(1));
        $this->assertFalse($d2->hasValue(2));

        // invalid input should return false
        $d3 = new Dictionary("not json");
        $this->assertFalse($d3->hasValue("any"));
    }

    public function testContains() {
        // contains should check if a value is present in a list
        $d1 = new Dictionary([ "x", "y" ]);
        $this->assertTrue($d1->contains("x"));
        $this->assertFalse($d1->contains("z"));

        // contains should work with integer values in a list
        $d2 = new Dictionary([ 1, 2, 3 ]);
        $this->assertTrue($d2->contains(2));
        $this->assertFalse($d2->contains(4));

        // contains should check if a value is present in a list of dictionaries
        $d3 = new Dictionary([[ "id" => "x" ], [ "id" => "y" ]]);
        $this->assertTrue($d3->contains("x", "id"));

        // contains should check if a key is present in an associative array
        $d4 = new Dictionary([ "k" => 1 ]);
        $this->assertTrue($d4->contains("k"));
        $this->assertFalse($d4->contains("missing"));

        // invalid input should return false
        $d5 = new Dictionary("not json");
        $this->assertFalse($d5->contains("any"));
    }

    public function testContainsInt() {
        // containsInt should check if an integer value is present in a list, even if the list contains strings
        $d1 = new Dictionary([ 1, 2, 3 ]);
        $this->assertTrue($d1->containsInt(2));
        $this->assertFalse($d1->containsInt(4));

        // containsInt should work with string values in a list
        $d2 = new Dictionary([ "2" => "v" ]);
        $this->assertTrue($d2->containsInt(2));
        $this->assertFalse($d2->containsInt(3));

        // invalid input should return false
        $d4 = new Dictionary("not json");
        $this->assertFalse($d4->containsInt(1));
    }

    public function testMerge() {
        $d1 = new Dictionary([ "x" => 1 ]);
        $d2 = new Dictionary([ "y" => 2 ]);
        $d1->merge($d2);

        $this->assertTrue($d1->has("y"));
        $this->assertEquals(2, $d1->get("y"));

        // merge should overwrite existing keys
        $d3 = new Dictionary([ "x" => 10 ]);
        $d1->merge($d3);
        $this->assertEquals(10, $d1->get("x"));

        // merge with empty Dictionary should do nothing
        $total = $d1->getTotal();
        $d1->merge(new Dictionary());
        $this->assertEquals($total, $d1->getTotal());
    }

    public function testPush() {
        // push should add a value to the data if the data is a list
        $d1 = new Dictionary([ 1 ]);
        $d1->push(2);
        $this->assertEquals([ 1, 2 ], $d1->toList());

        // push should add an array to the data if the data is a list
        $d2 = new Dictionary([[ "a" => 1 ]]);
        $d2->push(new Dictionary([ "b" => 2 ]));
        $this->assertCount(2, $d2->toList());

        // push should do nothing if the data is not a list
        $d3 = new Dictionary([ "k" => "v" ]);
        $d3->push("new");
        $this->assertFalse($d3->has("new"));

        // push should do nothing if the data is not a list, even if it is an array
        $d4 = new Dictionary([ "k" => "v" ]);
        $d4->push([ "new" ]);
        $this->assertFalse($d4->has("new"));

        // push should work on an empty Dictionary by creating a list
        $d5 = new Dictionary();
        $d5->push("first");
        $this->assertTrue($d5->isList());
        $this->assertEquals([ "first" ], $d5->toList());

        // invalid input should work as is an empty Dictionary
        $d6 = new Dictionary("not json");
        $d6->push("value");
        $this->assertTrue($d6->isList());
        $this->assertEquals([ "value" ], $d6->toList());
    }

    public function testSet() {
        $d1 = new Dictionary();
        $d1->set("k", "v");
        $this->assertEquals("v", $d1->get("k"));

        // set should work with numeric keys
        $d2 = new Dictionary();
        $d2->set(0, "zero");
        $this->assertEquals("zero", $d2->get(0));
    }

    public function testSetString() {
        $d1 = new Dictionary();
        $d1->setString("s", "str");
        $this->assertEquals("str", $d1->getString("s"));

        // setString should work with numeric keys
        $d2 = new Dictionary();
        $d2->setString(0, "zero");
        $this->assertEquals("zero", $d2->getString(0));
    }

    public function testSetInt() {
        $d1 = new Dictionary();
        $d1->setInt("n", 5);
        $this->assertEquals(5, $d1->getInt("n"));

        // setInt should work with numeric keys
        $d2 = new Dictionary();
        $d2->setInt(0, 100);
        $this->assertEquals(100, $d2->getInt(0));
    }

    public function testRemove() {
        $d1 = new Dictionary([ "a" => 1 ]);
        $d1->remove("a");
        $this->assertFalse($d1->has("a"));

        // removing a missing key should be safe
        $d1->remove("missing");
        $this->assertFalse($d1->has("missing"));

        // remove numeric key from a list
        $d2 = new Dictionary([ "first", "second", "third" ]);
        $d2->remove(1);
        $this->assertFalse($d2->has(1));
        $this->assertEquals(2, $d2->getTotal());

        // remove from nested dictionary
        $d3 = new Dictionary([ "sub" => [ "x" => 1, "y" => 2 ]]);
        $sub = $d3->getDict("sub");
        $sub->remove("x");
        $this->assertFalse($sub->has("x"));
    }

    public function testGet() {
        $d1 = new Dictionary([ "x" => "y" ]);
        $this->assertEquals("y", $d1->get("x"));
        $this->assertNull($d1->get("none"));

        // numeric keys
        $d2 = new Dictionary([ "first", "second" ]);
        $this->assertEquals("first", $d2->get(0));

        // array values are returned as arrays
        $d3 = new Dictionary([ "arr" => [ "a" => 1 ]]);
        $val = $d3->get("arr");
        $this->assertIsArray($val);
        $this->assertEquals([ "a" => 1 ], $val);

        // set then get
        $d4 = new Dictionary();
        $d4->set("new", "v");
        $this->assertEquals("v", $d4->get("new"));

        // invalid input should return null
        $d5 = new Dictionary("not json");
        $this->assertNull($d5->get("any"));
    }

    public function testGetBool() {
        $d = new Dictionary([
            "a"   => "",
            "b"   => "1",
            "c"   => 0,
            "d"   => "0",
            "e"   => true,
            "f"   => false,
            "g"   => "true",
            "h"   => "false",
            "arr" => [ 1 ],
        ]);

        // empty string and zero-like values are considered false
        $this->assertFalse($d->getBool("a"));
        $this->assertFalse($d->getBool("c"));
        $this->assertFalse($d->getBool("d"));

        // non-empty strings and true boolean are considered true
        $this->assertTrue($d->getBool("b"));
        $this->assertTrue($d->getBool("e"));
        $this->assertTrue($d->getBool("g"));

        // string "false" is non-empty so considered true by current semantics
        $this->assertTrue($d->getBool("h"));

        // explicit false and array values are false
        $this->assertFalse($d->getBool("f"));
        $this->assertFalse($d->getBool("arr"));

        // missing keys return false
        $this->assertFalse((new Dictionary())->getBool("missing"));
    }

    public function testGetInt() {
        $d = new Dictionary([
            "n"    => "3.7",
            "bad"  => [ 1 ],
            "sint" => "3",
            "neg"  => "-2.4",
            "x"    => "3.456",
        ]);

        // Numbers::toInt uses rounding, so 3.7 -> 4
        $this->assertEquals(4, $d->getInt("n"));

        // with 1 decimal -> 3.7 * 10 = 37
        $this->assertEquals(37, $d->getInt("n", 1));

        // integer-like string
        $this->assertEquals(3, $d->getInt("sint"));

        // negative with rounding
        $this->assertEquals(-2, $d->getInt("neg"));

        // decimals parameter scales and rounds: 3.456 * 100 = 345.6 -> 346
        $this->assertEquals(346, $d->getInt("x", 2));

        // missing key returns default (0) unless provided
        $this->assertEquals(0, $d->getInt("missing"));
        $this->assertEquals(7, $d->getInt("missing", 0, 7));

        // non-scalar value returns provided default
        $this->assertEquals(5, $d->getInt("bad", 0, 5));
    }

    public function testGetFloat() {
        $d = new Dictionary(["f" => "2.5", "i" => 3, "neg" => "-1.25", "arr" => [1]]);

        // numeric string -> float
        $this->assertEquals(2.5, $d->getFloat("f"));

        // integer stored as int should return float
        $this->assertEquals(3.0, $d->getFloat("i"));

        // negative float string
        $this->assertEquals(-1.25, $d->getFloat("neg"));

        // missing key returns default (0.0) or provided default
        $this->assertEquals(0.0, $d->getFloat("missing"));
        $this->assertEquals(1.23, $d->getFloat("missing", 1.23));

        // non-scalar value returns provided default
        $this->assertEquals(9.9, $d->getFloat("arr", 9.9));
    }

    public function testGetPrice() {
        $d = new Dictionary([
            "p"      => 123,
            "p_str"  => "150",
            "p_zero" => 0,
            "neg"    => -250,
            "arr"    => [ 1 ],
        ]);

        // cents as integer
        $this->assertEquals(1.23, $d->getPrice("p"));

        // numeric string representing cents
        $this->assertEquals(150, $d->getPrice("p_str"));

        // zero cents -> zero price
        $this->assertEquals(0.0, $d->getPrice("p_zero"));

        // negative cents (allowed) -> negative price
        $this->assertEquals(-2.50, $d->getPrice("neg"));

        // missing key returns default (0.0) or provided default
        $this->assertEquals(0.0, $d->getPrice("missing"));
        $this->assertEquals(9.99, $d->getPrice("missing", 9.99));

        // non-scalar returns provided default
        $this->assertEquals(5.50, $d->getPrice("arr", 5.50));
    }

    public function testGetString() {
        $d = new Dictionary([
            "s"    => 5,
            "str"  => "hello",
            "num"  => "3",
            "null" => null,
            "arr"  => [ "x" ],
        ]);

        // int converted to string
        $this->assertEquals("5", $d->getString("s"));

        // plain string
        $this->assertEquals("hello", $d->getString("str"));

        // numeric string preserved
        $this->assertEquals("3", $d->getString("num"));

        // null stored as value returns default (empty string)
        $this->assertEquals("", $d->getString("null"));

        // missing key returns provided default
        $this->assertEquals("def", $d->getString("missing", "def"));

        // non-scalar value returns provided default
        $this->assertEquals("fallback", $d->getString("arr", "fallback"));
    }

    public function testGetDate() {
        // from a date string
        $d1 = new Dictionary([ "date" => "2020-01-02" ]);
        $date = $d1->getDate("date");
        $this->assertInstanceOf(Date::class, $date);
        $this->assertTrue($date->isNotEmpty());
        $this->assertEquals(20200102, $date->toNumber());

        // from a timestamp integer
        $ts = strtotime("2021-03-04");
        $d2 = new Dictionary(["ts" => $ts]);
        $date2 = $d2->getDate("ts");
        $this->assertInstanceOf(Date::class, $date2);
        $this->assertEquals($ts, $date2->toTime());

        // from a Date instance
        $orig = Date::create("2019-12-31");
        $d3 = new Dictionary(["d" => $orig]);
        $date3 = $d3->getDate("d");
        $this->assertTrue($date3->isNotEmpty());
        $this->assertTrue($date3->isEqual($orig));

        // missing key returns empty Date
        $d4 = new Dictionary();
        $this->assertTrue($d4->getDate("no")->isEmpty());

        // non-scalar value returns empty Date
        $d5 = new Dictionary([ "arr" => [ 1 ] ]);
        $this->assertTrue($d5->getDate("arr")->isEmpty());
    }

    public function testGetDateParsed() {
        // parse common textual date
        $d1 = new Dictionary([ "date" => "2/1/2020" ]);
        $parsed = $d1->getDateParsed("date");
        $this->assertInstanceOf(Date::class, $parsed);
        $this->assertTrue($parsed->isNotEmpty());
        $this->assertEquals(20200102, $parsed->toNumber());

        // missing key -> empty Date
        $d2 = new Dictionary();
        $this->assertTrue($d2->getDateParsed("no")->isEmpty());

        // invalid text -> empty Date
        $d3 = new Dictionary([ "bad" => "not a date" ]);
        $this->assertTrue($d3->getDateParsed("bad")->isEmpty());
    }

    public function testGetKeys() {
        $d1 = new Dictionary(["a" => 1, "b" => 2]);
        $keys = $d1->getKeys();
        $this->assertContains("a", $keys);
        $this->assertContains("b", $keys);

        // keys are returned as strings
        foreach ($keys as $k) {
            $this->assertIsString($k);
        }

        // empty Dictionary returns empty keys list
        $this->assertEmpty((new Dictionary())->getKeys());

        // list-style data should return numeric keys as strings
        $d2 = new Dictionary(["x", "y"]);
        $keys2 = $d2->getKeys();
        $this->assertContains("0", $keys2);
        $this->assertContains("1", $keys2);
        $this->assertCount(2, $keys2);

        // mixed keys preserve their string representations
        $d3 = new Dictionary([0 => "zero", "one" => 1]);
        $k3 = $d3->getKeys();
        $this->assertContains("0", $k3);
        $this->assertContains("one", $k3);
    }

    public function testGetDict() {
        $d1 = new Dictionary([ "sub" => [ "x" => 1 ]]);
        $sub = $d1->getDict("sub");
        $this->assertInstanceOf(Dictionary::class, $sub);
        $this->assertEquals(1, $sub->get("x"));

        // missing key returns empty Dictionary
        $d2 = new Dictionary();
        $this->assertInstanceOf(Dictionary::class, $d2->getDict("no"));
        $this->assertTrue($d2->getDict("no")->isEmpty());

        // value already a Dictionary instance is returned correctly
        $orig = new Dictionary([ "y" => 2 ]);
        $d3   = new Dictionary([ "sub" => $orig ]);
        $sub2 = $d3->getDict("sub");
        $this->assertInstanceOf(Dictionary::class, $sub2);
        $this->assertEquals(2, $sub2->get("y"));

        // non-array/scalar value returns empty Dictionary
        $d4 = new Dictionary([ "s" => "string" ]);
        $this->assertTrue($d4->getDict("s")->isEmpty());

        // accessing list element by numeric index returns Dictionary
        $d5 = new Dictionary([[ "id" => "x" ]]);
        $item = $d5->getDict(0);
        $this->assertInstanceOf(Dictionary::class, $item);
        $this->assertEquals("x", $item->get("id"));

        // accessing list element by numeric index that is out of bounds returns empty Dictionary
        $this->assertTrue($d5->getDict(1)->isEmpty());
    }

    public function testFindDict() {
        // basic find in a list of associative arrays
        $list = [
            [ "id" => "a", "val" => 1 ],
            [ "id" => "b", "val" => 2 ],
            [ "noId" => 9 ],
        ];

        $d = new Dictionary($list);
        $found = $d->findDict("id", "b");
        $this->assertInstanceOf(Dictionary::class, $found);
        $this->assertEquals(2, $found->getInt("val"));

        // missing value returns empty Dictionary
        $not = $d->findDict("id", "z");
        $this->assertInstanceOf(Dictionary::class, $not);
        $this->assertTrue($not->isEmpty());

        // when the top-level data is a map (not a list) it should not search
        $map = new Dictionary([ "a" => [ "id" => "x", "val" => 10 ]]);
        $res = $map->findDict("id", "x");
        $this->assertInstanceOf(Dictionary::class, $res);
        $this->assertTrue($res->isEmpty());

        // multiple matches -> first match is returned
        $dup = new Dictionary([[ "id" => "d", "val" => 4 ], [ "id" => "d", "val" => 5 ]]);
        $first = $dup->findDict("id", "d");
        $this->assertEquals(4, $first->getInt("val"));
    }

    public function testGetList() {
        // proper nested list returns array of Dictionary
        $d = new Dictionary([ "items" => [[ "id" => "a" ], [ "id" => "b" ]] ]);
        $list = $d->getList("items");
        $this->assertIsArray($list);
        $this->assertCount(2, $list);
        $this->assertInstanceOf(Dictionary::class, $list[0]);
        $this->assertEquals("a", $list[0]->get("id"));

        // missing key returns empty array
        $this->assertEquals([], (new Dictionary())->getList("no"));

        // non-array value returns empty array
        $d2 = new Dictionary([ "not" => "a string" ]);
        $this->assertEquals([], $d2->getList("not"));

        // mixed list: array items become Dictionary, scalar items become empty Dictionary
        $d3 = new Dictionary([ "mixed" => [[ "id" => "x" ], "plain"] ]);
        $lst3 = $d3->getList("mixed");
        $this->assertInstanceOf(Dictionary::class, $lst3[0]);
        $this->assertEquals("x", $lst3[0]->get("id"));
        $this->assertInstanceOf(Dictionary::class, $lst3[1]);
        $this->assertTrue($lst3[1]->isEmpty());

        // empty nested list returns empty array (distinct from missing key)
        $d4 = new Dictionary([ "empty" => [] ]);
        $this->assertEquals([], $d4->getList("empty"));

        // objects inside list are converted into Dictionary
        $obj = new stdClass();
        $obj->p = "v";
        $d5 = new Dictionary([ "objs" => [ $obj ] ]);
        $l5 = $d5->getList("objs");
        $this->assertInstanceOf(Dictionary::class, $l5[0]);
        $this->assertEquals("v", $l5[0]->getString("p"));

        // nested arrays remain accessible through Dictionary getters
        $d6 = new Dictionary([ "items" => [[ "sub" => [ "a", "b" ], "vals" => [ 1, 2 ] ]]]);
        $l6 = $d6->getList("items");
        $this->assertEquals([ "a", "b" ], $l6[0]->getArray("sub"));
        $this->assertEquals([ 1, 2 ], $l6[0]->getInts("vals"));
    }

    public function testGetFirst() {
        // getFirst on top-level list returns the first Dictionary
        $d1 = new Dictionary([[ "id" => "a" ], [ "id" => "b" ]]);
        $f1 = $d1->getFirst();
        $this->assertInstanceOf(Dictionary::class, $f1);
        $this->assertEquals("a", $f1->get("id"));

        // getFirst with key access returns the first element of the nested list
        $d2 = new Dictionary([ "group" => [[ "n" => 1 ], [ "n" => 2 ]]]);
        $this->assertEquals(1, $d2->getFirst("group")->getInt("n"));

        // empty Dictionary returns empty Dictionary for getFirst
        $d3 = new Dictionary();
        $this->assertTrue($d3->getFirst()->isEmpty());
    }

    public function testGetLast() {
        // getLast on top-level list
        $d1 = new Dictionary([[ "id" => "a" ], [ "id" => "b" ]]);
        $l1 = $d1->getLast();
        $this->assertInstanceOf(Dictionary::class, $l1);
        $this->assertEquals("b", $l1->get("id"));

        // getLast with key access
        $d2 = new Dictionary([ "group" => [[ "n" => 1 ], [ "n" => 2 ]]]);
        $this->assertEquals(2, $d2->getLast("group")->getInt("n"));

        // empty cases return empty Dictionary
        $d3 = new Dictionary();
        $this->assertTrue($d3->getLast()->isEmpty());
    }

    public function testGetInts() {
        $d1 = new Dictionary([ "ints" => [ "1", 2, "3" ]]);
        $this->assertEquals([ 1, 2, 3 ], $d1->getInts("ints"));

        // missing key -> empty list
        $d2 = new Dictionary();
        $this->assertEquals([], $d2->getInts("no"));

        // non-list value should return empty list
        $d3 = new Dictionary([ "ints" => "not an array" ]);
        $this->assertEquals([], $d3->getInts("ints"));

        // decimals should be rounded
        $d4 = new Dictionary([ "ints" => [ "2.5", "-1.2" ]]);
        $this->assertEquals([ 3, -1 ], $d4->getInts("ints"));
    }

    public function testGetStrings() {
        $d1 = new Dictionary([ "strings" => [ "a", "", "b" ]]);
        $this->assertEquals([ "a", "", "b" ], $d1->getStrings("strings"));
        $this->assertEquals([ "a", "b" ], $d1->getStrings("strings", true));

        // missing key -> empty list
        $d2 = new Dictionary();
        $this->assertEquals([], $d2->getStrings("no"));

        // non-list value should return a list with the single string value
        $d3 = new Dictionary([ "strings" => "not an array" ]);
        $this->assertEquals([ "not an array" ], $d3->getStrings("strings"));

        // numeric values should be converted to strings
        $d4 = new Dictionary([ "strings" => [ 1, 2 ]]);
        $this->assertEquals([ "1", "2" ], $d4->getStrings("strings"));
    }

    public function testGetArray() {
        $d1 = new Dictionary([ "arr" => [ "x" ]]);
        $this->assertEquals([ "x" ], $d1->getArray("arr"));

        // missing key returns empty array
        $d2 = new Dictionary();
        $this->assertEquals([], $d2->getArray("no"));

        // non-array returns empty array
        $d3 = new Dictionary([ "arr" => "str" ]);
        $this->assertEquals([], $d3->getArray("arr"));

        // array with non-string values should be returned as-is
        $d4 = new Dictionary([ "arr" => [ 1, "x" ]]);
        $this->assertEquals([ 1, "x" ], $d4->getArray("arr"));
    }

    public function testGetJSON() {
        $d1 = new Dictionary([ "json" => [ "k" => "v" ]]);
        $json = $d1->getJSON("json");
        $this->assertIsString($json);
        $this->assertStringContainsString('"k"', $json);

        // missing key -> empty json array
        $d2 = new Dictionary();
        $this->assertEquals("[]", $d2->getJSON("no"));

        // non-array value should return empty JSON array
        $d3 = new Dictionary([ "json" => "not an array" ]);
        $this->assertEquals("[]", $d3->getJSON("json"));

        // invalid input should return empty JSON array
        $d4 = new Dictionary("not json");
        $this->assertEquals("[]", $d4->getJSON("any"));
    }

    public function testDecodeAsArray() {
        // decodeAsArray: objects and arrays should decode correctly
        $d = new Dictionary([ "jsonObj" => '{"k":"v"}', "jsonArr" => '["x", 2]' ]);
        $this->assertEquals([ "k" => "v" ], $d->decodeAsArray("jsonObj"));
        $this->assertEquals([ "x", 2 ], $d->decodeAsArray("jsonArr"));

        // invalid JSON -> empty array
        $d2 = new Dictionary([ "bad" => "not json" ]);
        $this->assertEquals([], $d2->decodeAsArray("bad"));

        // missing key -> empty array
        $d3 = new Dictionary();
        $this->assertEquals([], $d3->decodeAsArray("missing"));
    }

    public function testDecodeAsStrings() {
        // decodeAsStrings: string arrays, numeric arrays and empty arrays
        $d1 = new Dictionary([
            "jsonStr"   => '["a","b"]',
            "jsonNum"   => '[1,2]',
            "jsonEmpty" => '[]'
        ]);

        $this->assertEquals([ "a", "b" ], $d1->decodeAsStrings("jsonStr"));
        $this->assertEquals([ "1", "2" ], $d1->decodeAsStrings("jsonNum"));
        $this->assertEquals([], $d1->decodeAsStrings("jsonEmpty"));

        // invalid JSON -> empty list
        $d2 = new Dictionary([ "bad" => "not json" ]);
        $this->assertEquals([], $d2->decodeAsStrings("bad"));

        // missing key -> empty list
        $d3 = new Dictionary();
        $this->assertEquals([], $d3->decodeAsStrings("missing"));
    }

    public function testCreateMap() {
        $d1 = new Dictionary([[ "id" => "x", "v" => 1 ], [ "id" => "y", "v" => 2 ]]);
        $map = $d1->createMap("id");
        $this->assertInstanceOf(Dictionary::class, $map);
        $this->assertTrue($map->has("x"));

        // createMap with missing key should skip those items
        $d2 = new Dictionary([[ "id" => "x", "v" => 1 ], [ "v" => 2 ]]);
        $map2 = $d2->createMap("id");
        $this->assertTrue($map2->has("x"));
        $this->assertFalse($map2->has("y"));

        // createMap with non-string keys should convert them to strings
        $d3 = new Dictionary([[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]]);
        $map3 = $d3->createMap("id");
        $this->assertTrue($map3->has("1"));
        $this->assertTrue($map3->has("2"));
    }

    public function testToArray() {
        $d1 = new Dictionary([ "a" => "1", "b" => 2, "c" => [ "x" ]]);
        $arr = $d1->toArray();
        $this->assertIsArray($arr);
        $this->assertEquals("1", $arr["a"]);
        $this->assertEquals(2, $arr["b"]);
        $this->assertIsArray($arr["c"]);

        // nested dictionaries should be converted to arrays
        $d2 = new Dictionary([ "sub" => [ "k" => "v" ]]);
        $a2 = $d2->toArray();
        $this->assertIsArray($a2["sub"]);
        $this->assertEquals("v", $a2["sub"]["k"]);

        // invalid input should return empty array
        $d3 = new Dictionary("not json");
        $this->assertEquals([], $d3->toArray());
    }

    public function testToList() {
        // list-style data remains a plain indexed array
        $d1 = new Dictionary([ 1, "x" ]);
        $this->assertEquals([ 1, "x" ], $d1->toList());

        // associative map -> toList should return a list of values
        $d2 = new Dictionary([ "a" => "1", "b" => "2" ]);
        $lst = $d2->toList();
        $this->assertIsArray($lst);
        $this->assertCount(2, $lst);

        // nested dictionaries should be converted to arrays in the list
        $d3 = new Dictionary([[ "k" => "v" ]]);
        $lst3 = $d3->toList();
        $this->assertIsArray($lst3);
        $this->assertIsArray($lst3[0]);
        $this->assertEquals("v", $lst3[0]["k"]);

        // invalid input should return empty list
        $d4 = new Dictionary("not json");
        $this->assertEquals([], $d4->toList());
    }

    public function testToStringsMap() {
        $d1 = new Dictionary([ "a" => 1, "b" => "", "c" => null ]);
        $map = $d1->toStringsMap();
        $this->assertIsArray($map);
        $this->assertEquals("1", $map["a"]);
        $this->assertEquals("", $map["b"]);
        // null becomes empty string
        $this->assertEquals("", $map["c"]);

        // numeric keys should remain representable as strings
        $d2 = new Dictionary([["id" => 3, "v" => "x"]]);
        $created = $d2->createMap("id");
        $this->assertInstanceOf(Dictionary::class, $created);

        // invalid input should return empty map
        $d3 = new Dictionary("not json");
        $this->assertEquals([], $d3->toStringsMap());
    }

    public function testToIntStringMap() {
        $d = new Dictionary([ "1" => "one", "2" => "two" ]);
        $map = $d->toIntStringMap();
        $this->assertIsArray($map);
        $this->assertArrayHasKey("1", $map);
        $this->assertEquals("one", $map["1"]);

        // numeric keys should remain representable as strings
        $d2 = new Dictionary([[ "id" => 3, "v" => "x" ]]);
        $created = $d2->createMap("id");
        $this->assertInstanceOf(Dictionary::class, $created);

        // invalid input should return empty map
        $d3 = new Dictionary("not json");
        $this->assertEquals([], $d3->toIntStringMap());
    }

    public function testToStringIntMap() {
        $d = new Dictionary([ "a" => 1, "b" => "2" ]);
        $map = $d->toStringIntMap();
        $this->assertIsArray($map);
        $this->assertEquals(1, $map["a"]);
        $this->assertEquals(2, $map["b"]);

        // invalid input should return empty map
        $d2 = new Dictionary("not json");
        $this->assertEquals([], $d2->toStringIntMap());
    }

    public function testToStringMixedMap() {
        $d = new Dictionary([ "a" => 1, "b" => "s" ]);
        $map = $d->toStringMixedMap();
        $this->assertIsArray($map);
        $this->assertArrayHasKey("a", $map);
        $this->assertArrayHasKey("b", $map);

        // invalid input should return empty map
        $d2 = new Dictionary("not json");
        $this->assertEquals([], $d2->toStringMixedMap());

        // nested dictionaries should be converted to arrays
        $d3 = new Dictionary([ "sub" => [ "k" => "v" ]]);
        $map3 = $d3->toStringMixedMap();
        $this->assertIsArray($map3["sub"]);
        $this->assertEquals("v", $map3["sub"]["k"]);
    }

    public function testToStrings() {
        $d1 = new Dictionary([ "a" => "1", "b" => "", "c" => 3 ]);
        $this->assertEquals([ "1","","3" ], $d1->toStrings());

        // with nested arrays, non-scalar values are stringified or skipped
        $d2 = new Dictionary([ "x" => [ 1 ], "y" => null ]);
        $this->assertIsArray($d2->toStrings());

        // invalid input returns empty list
        $d3 = new Dictionary("not json");
        $this->assertEquals([], $d3->toStrings());
    }

    public function testToInts() {
        // numeric strings become ints; empty strings skipped when requested
        $d1 = new Dictionary([ "a" => "1", "b" => "", "c" => "2.5" ]);
        $this->assertEquals([ 1, 3 ], $d1->toInts(true));
        $this->assertIsArray($d1->toInts());

        // negative and decimal rounding behavior
        $d2 = new Dictionary([ "n" => "-1.6", "p" => "2.4" ]);
        $this->assertEquals([ -2, 2 ], $d2->toInts());

        // invalid input returns empty list
        $d3 = new Dictionary("not json");
        $this->assertEquals([], $d3->toInts(true));
    }

    public function testToJSON() {
        $d1 = new Dictionary([ "a" => "1", "b" => "" ]);
        $json = $d1->toJSON();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey("a", $decoded);

        // empty/invalid dictionary serializes to JSON (should decode to array)
        $d2 = new Dictionary("not json");
        $j2 = $d2->toJSON();
        $this->assertIsString($j2);
        $this->assertIsArray(json_decode($j2, true));
    }

    public function testCount() {
        $d1 = new Dictionary([ "a" => 1, "b" => 2 ]);
        $this->assertEquals(2, $d1->count());

        // empty Dictionary should count as 0
        $d2 = new Dictionary();
        $this->assertEquals(0, $d2->count());

        // list-style data should count the number of items
        $d3 = new Dictionary([ "x", "y", "z" ]);
        $this->assertEquals(3, $d3->count());

        // invalid input should count as 0
        $d4 = new Dictionary("not json");
        $this->assertEquals(0, $d4->count());
    }

    public function testIterator() {
        $d1 = new Dictionary([ "a" => 1, "b" => 2 ]);
        $collected = [];
        foreach ($d1 as $k => $v) {
            $this->assertInstanceOf(Dictionary::class, $v);
            $collected[] = $k;
        }
        $this->assertContains("a", $collected);
        $this->assertContains("b", $collected);
        $this->assertCount(2, $collected);

        // iterating over empty Dictionary should not yield any items
        $d2 = new Dictionary();
        $count = 0;
        foreach ($d2 as $k => $v) {
            $count++;
            $this->assertInstanceOf(Dictionary::class, $v);
        }
        $this->assertEquals(0, $count);
    }

    public function testJsonSerialize() {
        $d1 = new Dictionary([ "a" => 1, "b" => 2 ]);
        $serialized = $d1->jsonSerialize();
        $this->assertIsArray($serialized);
        $this->assertArrayHasKey("a", $serialized);
        $this->assertEquals(1, $serialized["a"]);
        // ensure it can be encoded to JSON
        $this->assertIsString(json_encode($serialized));

        // invalid input should serialize to empty array
        $d2 = new Dictionary("not json");
        $s2 = $d2->jsonSerialize();
        $this->assertIsArray($s2);
        $this->assertEmpty($s2);

        // nested dictionaries should be serialized as arrays
        $d3 = new Dictionary([ "sub" => [ "k" => "v" ]]);
        $s3 = $d3->jsonSerialize();
        $this->assertIsArray($s3["sub"]);
        $this->assertEquals("v", $s3["sub"]["k"]);

        // ensure that json_encode on the Dictionary itself works as expected
        $json = json_encode($d3);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertIsArray($decoded["sub"]);
        $this->assertEquals("v", $decoded["sub"]["k"]);
    }
}
