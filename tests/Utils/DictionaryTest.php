<?php
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Dictionary;

use Traversable;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

enum TestDictionaryEnum implements Enum {
    use IsEnum;

    case None;
    case Key;
    case Value;
}

class DictionaryTest extends TestCase {

    #[DataProvider("providerConstruct")]
    public function testConstruct(mixed $input, int|string $expectedKey, mixed $expectedValue, bool $shouldBeEmpty): void {
        $d = new Dictionary($input);

        if ($shouldBeEmpty) {
            $this->assertTrue($d->isEmpty());
        } else {
            $this->assertIsArray($d->toArray());
            $this->assertEquals($expectedValue, $d->get($expectedKey));
        }
    }

    public static function providerConstruct(): array {
        $obj = new stdClass();
        $obj->x = 10;
        $obj->y = "z";

        $json = json_encode([ "m" => 5, "n" => "v" ]);
        $dict = new Dictionary([ "k" => "val" ]);

        return [
            "array"           => [ [ "a" => 1, "b" => 2 ], "a", 1, false ],
            "list"            => [ [ "first", "second" ], 1, "second", false ],
            "object"          => [ $obj, "x", 10, false ],
            "json_string"     => [ $json, "m", 5, false ],
            "dictionary"      => [ $dict, "k", "val", false ],
            "strings"         => [ "a,b,c", 1, "b", false ],
            "spaced_strings"  => [ "a, b , c", 1, "b", false ],
            "empty_between"   => [ "a,,b", 1, "b", false ],
            "simple_string"   => [ "simple string", 0, "simple string", false ],
            "nested_array"    => [ [ "a" => [ "b" => 1 ] ], "a", [ "b" => 1 ], false ],
            "null_value"      => [ [ "a" => null ], "a", null, false ],
            "empty_object"    => [ new stdClass(), 0, null, true ],
            "empty_array"     => [ [], 0, null, true ],
            "invalid_integer" => [ 12345, 0, null, true ],
            "invalid_float"   => [ 1.5, 0, null, true ],
            "invalid_bool"    => [ true, 0, null, true ],
            "invalid_null"    => [ null, 0, null, true ],
            "invalid_string"  => [ "", 0, null, true ],
        ];
    }


    #[DataProvider("providerClone")]
    public function testClone(mixed $input): void {
        $d     = new Dictionary($input);
        $clone = $d->clone();

        $this->assertNotSame($d, $clone);
        $this->assertTrue($d->isEqual($clone));

        $clone->set("added", "value");
        $this->assertTrue($clone->has("added"));
        $this->assertFalse($d->has("added"));
    }

    public static function providerClone(): array {
        return [
            "associative array" => [ [ "a" => 1, "b" => 2 ] ],
            "list style data"   => [ [ "x", "y", "z" ] ],
            "nested array"      => [ [ "a" => [ "x" => 1 ], "b" => [ 1, 2 ] ] ],
            "list of arrays"    => [ [[ "id" => 1 ], [ "id" => 2 ]] ],
            "null value"        => [ [ "a" => null ] ],
            "empty dictionary"  => [ [] ],
            "invalid input"     => [ "" ],
        ];
    }

    #[DataProvider("providerClone")]
    public function testACloneKeepsNothingOfTheOriginal(mixed $input): void {
        $original = new Dictionary($input);
        $clone    = $original->clone();
        $before   = $original->toArray();

        foreach ($clone->getKeys() as $key) {
            $clone->set($key, "changed");
            $clone->remove($key);
        }

        $this->assertTrue($clone->isEmpty());
        $this->assertEquals($before, $original->toArray());
    }


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(mixed $input, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"     => [ [], true ],
            "non empty" => [ [ "a" => 1 ], false ],
        ];
    }


    #[DataProvider("providerIsNotEmpty")]
    public function testIsNotEmpty(mixed $input, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isNotEmpty());
    }

    public static function providerIsNotEmpty(): array {
        return [
            "empty"     => [ [], false ],
            "non empty" => [ [ "a" => 1 ], true ],
        ];
    }


    #[DataProvider("providerGetTotal")]
    public function testGetTotal(mixed $input, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getTotal());
    }

    public static function providerGetTotal(): array {
        return [
            "associative array" => [ [ "a" => 1, "b" => 2 ], 2 ],
            "list of arrays"    => [ [[ "id" => "x" ], [ "id" => "y" ]], 2 ],
            "invalid input"     => [ "", 0 ],
            "empty dictionary"  => [ [], 0 ],
        ];
    }


    #[DataProvider("providerIsEqual")]
    public function testIsEqual(mixed $input1, mixed $input2, bool $expected): void {
        $d1 = new Dictionary($input1);
        $d2 = new Dictionary($input2);
        $this->assertEquals($expected, $d1->isEqual($d2));
    }

    public static function providerIsEqual(): array {
        return [
            "equal dictionaries"   => [ [ "k" => "v" ], [ "k" => "v" ], true ],
            "unequal dictionaries" => [ [ "k" => "v" ], [ "k" => "x" ], false ],
            "invalid input"        => [ "not json", [ "k" => "v" ], false ],
        ];
    }


    #[DataProvider("providerIsNotEqual")]
    public function testIsNotEqual(mixed $input1, mixed $input2, bool $expected): void {
        $d1 = new Dictionary($input1);
        $d2 = new Dictionary($input2);
        $this->assertEquals($expected, $d1->isNotEqual($d2));
    }

    public static function providerIsNotEqual(): array {
        return [
            "unequal dictionaries" => [ [ "k" => "v" ], [ "k" => "x" ], true ],
            "equal dictionaries"   => [ [ "k" => "v" ], [ "k" => "v" ], false ],
            "invalid input"        => [ "", [ "k" => "v" ], true ],
        ];
    }


    #[DataProvider("providerIsList")]
    public function testIsList(mixed $input, string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isList($key));
    }

    public static function providerIsList(): array {
        return [
            "top level list"    => [ [ 1, 2, 3 ], "", true ],
            "list of arrays"    => [ [[ "a" => 1 ]], "", true ],
            "nested list"       => [ [ "list" => [ "a", "b" ] ], "list", true ],
            "nested empty list" => [ [ "list" => [] ], "list", true ],
            "non list array"    => [ [ "a" => "b" ], "", false ],
            "gapped keys"       => [ [ 0 => "a", 2 => "b" ], "", false ],
            "nested map"        => [ [ "a" => [ "x" => 1 ] ], "a", false ],
            "nested scalar"     => [ [ "a" => "b" ], "a", false ],
            "missing key"       => [ [ "a" => 1 ], "nope", false ],
            "empty dictionary"  => [ [], "", false ],
            "empty with key"    => [ [], "any", false ],
            "invalid input"     => [ "", "", false ],
        ];
    }


    #[DataProvider("providerIsArrayList")]
    public function testIsArrayList(mixed $input, string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isArrayList($key));
    }

    public static function providerIsArrayList(): array {
        return [
            "top level array list" => [ [[ "a" => 1 ], [ "a" => 2 ]], "", true ],
            "nested array list"    => [ [ "key" => [[ "x" => 1 ]] ], "key", true ],
            "list of scalars"      => [ [ "a", "b" ], "", false ],
            "list of objects"      => [ [ new stdClass() ], "", true ],
            "non numeric keys"     => [ [ "a" => [ "x" => 1 ] ], "a", false ],

            // Only the first element is looked at, so a list that turns to
            // scalars after it still counts and one that starts with them does not
            "array first"          => [ [[ "a" => 1 ], "b" ], "", true ],
            "scalar first"         => [ [ "b", [ "a" => 1 ]], "", false ],
            "missing key"          => [ [ "a" => 1 ], "nope", false ],
            "empty dictionary"     => [ [], "", false ],
            "empty with key"       => [ [], "any", false ],
            "invalid input"        => [ "not json", "", false ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->has($key));
    }

    public static function providerHas(): array {
        return [
            "associative key exists"  => [ [ "a" => 1 ], "a", true ],
            "associative key missing" => [ [ "a" => 1 ], "missing", false ],
            "numeric key exists"      => [ [ "x", "y" ], 0, true ],
            "numeric key missing"     => [ [ "x", "y" ], 2, false ],
            "enum key exists"         => [ [ "Key" => "value" ], TestDictionaryEnum::Key, true ],
            "enum key missing"        => [ [ "Key" => "value" ], TestDictionaryEnum::Value, false ],
            "zero value"              => [ [ "k" => 0 ], "k", true ],
            "empty string value"      => [ [ "k2" => "" ], "k2", true ],
            "invalid input"           => [ "not json", "any", false ],
        ];
    }


    #[DataProvider("providerHasValue")]
    public function testHasValue(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->hasValue($key));
    }

    public static function providerHasValue(): array {
        return [
            "assoc empty string"    => [ [ "a" => "" ], "a", false ],
            "assoc non empty"       => [ [ "b" => "x" ], "b", true ],
            "assoc zero"            => [ [ "c" => 0 ], "c", false ],
            "assoc key missing"     => [ [ "a" => "x" ], "missing", false ],
            "numeric key empty"     => [ [ "", "y" ], 0, false ],
            "numeric key non empty" => [ [ "", "y" ], 1, true ],
            "enum key empty"        => [ [ "Key" => "" ], TestDictionaryEnum::Key, false ],
            "enum key non empty"    => [ [ "Key" => "value" ], TestDictionaryEnum::Key, true ],

            "list first empty"      => [ [ "", "y", 0 ], 0, false ],
            "list second non empty" => [ [ "", "y", 0 ], 1, true ],
            "list third zero"       => [ [ "", "y", 0 ], 2, false ],

            "invalid input"         => [ "not json", "any", false ],
        ];
    }


    #[DataProvider("providerContains")]
    public function testContains(mixed $input, int|string|null $key, mixed $value, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->contains($value, $key));
    }

    public static function providerContains(): array {
        return [
            "list string found"     => [ [ "x", "y" ], null, "x", true ],
            "list string not found" => [ [ "x", "y" ], null, "z", false ],
            "list int found"        => [ [ 1, 2, 3 ], null, 2, true ],
            "list int not found"    => [ [ 1, 2, 3 ], null, 4, false ],
            "list of dicts found"   => [ [[ "id" => "x" ], [ "id" => "y" ]], "id", "x", true ],
            "assoc key found"       => [ [ "k" => 1 ], null, "k", true ],
            "assoc key not found"   => [ [ "k" => 1 ], null, "missing", false ],
            "invalid input"         => [ "not json", null, "any", false ],
        ];
    }


    #[DataProvider("providerContainsInt")]
    public function testContainsInt(mixed $input, int $value, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->containsInt($value));
    }

    public static function providerContainsInt(): array {
        return [
            "list int found"           => [ [ 1, 2, 3 ], 2, true ],
            "list int not found"       => [ [ 1, 2, 3 ], 4, false ],
            "string key int found"     => [ [ "2" => "v" ], 2, true ],
            "string key int not found" => [ [ "2" => "v" ], 3, false ],
            "invalid input"            => [ "not json", 1, false ],
        ];
    }


    #[DataProvider("providerMerge")]
    public function testMerge(mixed $input1, mixed $input2, int|string $checkKey, mixed $expectedValue, int $expectedTotal): void {
        $d1 = new Dictionary($input1);
        $d2 = new Dictionary($input2);
        $d1->merge($d2);

        $this->assertEquals($expectedValue, $d1->get($checkKey));
        $this->assertEquals($expectedTotal, $d1->getTotal());
    }

    public static function providerMerge(): array {
        return [
            "new key"       => [ [ "x" => 1 ], [ "y" => 2 ], "y", 2, 2 ],
            "overwrite key" => [ [ "x" => 1, "y" => 2 ], [ "x" => 10 ], "x", 10, 2 ],
            "empty dict"    => [ [ "x" => 1, "y" => 2 ], [], "x", 1, 2 ],
        ];
    }


    #[DataProvider("providerPush")]
    public function testPush(mixed $input, mixed $pushValue, array $expectedList, bool $shouldBeListed): void {
        $d = new Dictionary($input);
        $d->push($pushValue);

        if ($shouldBeListed) {
            $this->assertTrue($d->isList());
            $this->assertEquals($expectedList, $d->toList());
        } else {
            $this->assertFalse($d->has("new"));
        }
    }

    public static function providerPush(): array {
        return [
            "scalar to list"      => [ [ 1 ], 2, [ 1, 2 ], true ],
            "dict to list"        => [ [[ "a" => 1 ]], new Dictionary([ "b" => 2 ]), [[ "a" => 1 ], [ "b" => 2 ]], true ],
            "to assoc map"        => [ [ "k" => "v" ], "new", [], false ],
            "array to assoc map"  => [ [ "k" => "v" ], [ "new" ], [], false ],
            "to empty dictionary" => [ [], "first", [ "first" ], true ],
            "to invalid input"    => [ "", "value", [ "value" ], true ],
        ];
    }


    #[DataProvider("providerSet")]
    public function testSet(mixed $input, Enum|int|string $key, mixed $value): void {
        $d = new Dictionary($input);
        $d->set($key, $value);
        $this->assertEquals($value, $d->get($key));
    }

    public static function providerSet(): array {
        return [
            "string value" => [ [], "k", "v" ],
            "numeric key"  => [ [], 0, "zero" ],
            "enum key"     => [ [], TestDictionaryEnum::Key, "value" ],
            "int value"    => [ [], "k", 5 ],
            "float value"  => [ [], "k", 1.5 ],
            "bool value"   => [ [], "k", true ],
            "array value"  => [ [], "k", [ "a" => 1 ] ],
            "overwrites"   => [ [ "k" => "old" ], "k", "new" ],
        ];
    }


    // set takes any value at all, so it is the one that has to notice an Enum.
    // setEnum only ever gets one, and asserting through get() would not show
    // that it was written down as its string

    #[DataProvider("providerSetEnumValue")]
    public function testSetTurnsAnEnumValueIntoItsString(mixed $input, Enum|int|string $key, Enum $value, array $expected): void {
        $d = new Dictionary($input);
        $d->set($key, $value);
        $this->assertSame($expected, $d->toArray());
    }

    public static function providerSetEnumValue(): array {
        return [
            "string key"  => [ [], "k", TestDictionaryEnum::Value, [ "k" => "Value" ] ],
            "enum key"    => [ [], TestDictionaryEnum::Key, TestDictionaryEnum::Value, [ "Key" => "Value" ] ],
            "numeric key" => [ [], 0, TestDictionaryEnum::Key, [ "0" => "Key" ] ],
            "overwrites"  => [ [ "k" => "old" ], "k", TestDictionaryEnum::Key, [ "k" => "Key" ] ],

            // None is the empty case, so it is written down as nothing at all
            "none case"   => [ [], "k", TestDictionaryEnum::None, [ "k" => "" ] ],
        ];
    }


    #[DataProvider("providerSetString")]
    public function testSetString(Enum|int|string $key, string $value): void {
        $d = new Dictionary();
        $d->setString($key, $value);
        $this->assertEquals($value, $d->getString($key));
    }

    public static function providerSetString(): array {
        return [
            "string key"  => [ "s", "str" ],
            "numeric key" => [ 0, "zero" ],
            "enum key"    => [ TestDictionaryEnum::Key, "value" ],
        ];
    }


    #[DataProvider("providerSetInt")]
    public function testSetInt(Enum|int|string $key, int $value): void {
        $d = new Dictionary();
        $d->setInt($key, $value);
        $this->assertEquals($value, $d->getInt($key));
    }

    public static function providerSetInt(): array {
        return [
            "string key"  => [ "n", 5 ],
            "numeric key" => [ 0, 100 ],
            "enum key"    => [ TestDictionaryEnum::Key, 10 ],
        ];
    }


    #[DataProvider("providerSetEnum")]
    public function testSetEnum(Enum|int|string $key, Enum $value): void {
        $d = new Dictionary();
        $d->setEnum($key, $value);
        $this->assertEquals($value->toString(), $d->getString($key));
    }

    public static function providerSetEnum(): array {
        return [
            "string key"  => [ "e", TestDictionaryEnum::Value ],
            "numeric key" => [ 0, TestDictionaryEnum::Value ],
            "enum key"    => [ TestDictionaryEnum::Key, TestDictionaryEnum::Value ],
        ];
    }


    #[DataProvider("providerRemove")]
    public function testRemove(mixed $input, Enum|int|string $key, bool $shouldExist, int $expectedTotal): void {
        $d = new Dictionary($input);
        $d->remove($key);
        $this->assertEquals($shouldExist, $d->has($key));
        $this->assertEquals($expectedTotal, $d->getTotal());
    }

    public static function providerRemove(): array {
        return [
            "associative key"    => [ [ "a" => 1 ], "a", false, 0 ],
            "missing key"        => [ [ "a" => 1 ], "missing", false, 1 ],
            "numeric key list"   => [ [ "first", "second", "third" ], 1, false, 2 ],
            "enum key"           => [ [ "Key" => "value" ], TestDictionaryEnum::Key, false, 0 ],

            // has() reads all four of these as absent, so the total is what says
            // whether the key was really taken out
            "null value"         => [ [ "k" => null, "a" => 1 ], "k", false, 1 ],
            "false value"        => [ [ "k" => false, "a" => 1 ], "k", false, 1 ],
            "zero value"         => [ [ "k" => 0, "a" => 1 ], "k", false, 1 ],
            "empty string value" => [ [ "k" => "", "a" => 1 ], "k", false, 1 ],
            "empty array value"  => [ [ "k" => [], "a" => 1 ], "k", false, 1 ],
            "only key"           => [ [ "k" => null ], "k", false, 0 ],
        ];
    }


    #[DataProvider("providerGet")]
    public function testGet(mixed $input, Enum|int|string $key, mixed $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->get($key));
    }

    public static function providerGet(): array {
        return [
            "string value"  => [ [ "x" => "y" ], "x", "y" ],
            "missing key"   => [ [ "x" => "y" ], "none", null ],
            "numeric key"   => [ [ "first", "second" ], 0, "first" ],
            "enum key"      => [ [ "Key" => "value" ], TestDictionaryEnum::Key, "value" ],
            "array value"   => [ [ "arr" => [ "a" => 1 ]], "arr", [ "a" => 1 ] ],
            "set then get"  => [ [], "new", null ],
            "invalid input" => [ "not json", "any", null ],
        ];
    }


    #[DataProvider("providerGetBool")]
    public function testGetBool(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getBool($key));
    }

    public static function providerGetBool(): array {
        return [
            "empty string"   => [ [ "a" => "" ], "a", false ],
            "numeric string" => [ [ "b" => "1" ], "b", true ],
            "zero int"       => [ [ "c" => 0 ], "c", false ],
            "zero string"    => [ [ "d" => "0" ], "d", false ],
            "true bool"      => [ [ "e" => true ], "e", true ],
            "false bool"     => [ [ "f" => false ], "f", false ],
            "true string"    => [ [ "g" => "true" ], "g", true ],
            "false string"   => [ [ "h" => "false" ], "h", true ],
            "array value"    => [ [ "arr" => [ 1 ] ], "arr", false ],
            "enum key"       => [ [ "Key" => true ], TestDictionaryEnum::Key, true ],
            "missing key"    => [ [], "missing", false ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(mixed $input, Enum|int|string $key, int $decimals, int $default, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getInt($key, $decimals, $default));
    }

    public static function providerGetInt(): array {
        return [
            "rounding up"         => [ [ "n" => "3.7" ], "n", 0, 0, 4 ],
            "with decimals"       => [ [ "n" => "3.7" ], "n", 1, 0, 37 ],
            "integer string"      => [ [ "sint" => "3" ], "sint", 0, 0, 3 ],
            "negative rounding"   => [ [ "neg" => "-2.4" ], "neg", 0, 0, -2 ],
            "decimals scaling"    => [ [ "x" => "3.456" ], "x", 2, 0, 346 ],
            "missing key default" => [ [], "missing", 0, 0, 0 ],
            "missing key custom"  => [ [], "missing", 0, 7, 7 ],
            "non scalar default"  => [ [ "bad" => [ 1 ] ], "bad", 0, 5, 5 ],
            "enum key"            => [ [ "Key" => "3.2" ], TestDictionaryEnum::Key, 0, 0, 3 ],
        ];
    }


    #[DataProvider("providerGetFloat")]
    public function testGetFloat(mixed $input, Enum|int|string $key, float $default, float $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getFloat($key, $default));
    }

    public static function providerGetFloat(): array {
        return [
            "numeric string"     => [ [ "f" => "2.5" ], "f", 0.0, 2.5 ],
            "integer"            => [ [ "i" => 3 ], "i", 0.0, 3.0 ],
            "negative float"     => [ [ "neg" => "-1.25" ], "neg", 0.0, -1.25 ],
            "missing key"        => [ [], "missing", 0.0, 0.0 ],
            "missing key custom" => [ [], "missing", 1.23, 1.23 ],
            "non scalar default" => [ [ "arr" => [ 1 ] ], "arr", 9.9, 9.9 ],
            "enum key"           => [ [ "Key" => "3.2" ], TestDictionaryEnum::Key, 0.0, 3.2 ],
        ];
    }


    #[DataProvider("providerGetPrice")]
    public function testGetPrice(mixed $input, Enum|int|string $key, float $default, float $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getPrice($key, $default));
    }

    public static function providerGetPrice(): array {
        return [
            "cents as integer"    => [ [ "p" => 123 ], "p", 0.0, 1.23 ],
            "numeric string"      => [ [ "p_str" => "150" ], "p_str", 0.0, 150.0 ],
            "zero cents"          => [ [ "p_zero" => 0 ], "p_zero", 0.0, 0.0 ],
            "negative cents"      => [ [ "neg" => -250 ], "neg", 0.0, -2.50 ],
            "missing key default" => [ [], "missing", 0.0, 0.0 ],
            "missing key custom"  => [ [], "missing", 9.99, 9.99 ],
            "non scalar default"  => [ [ "arr" => [ 1 ] ], "arr", 5.50, 5.50 ],
            "enum key"            => [ [ "Key" => 250 ], TestDictionaryEnum::Key, 0.0, 2.50 ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(mixed $input, Enum|int|string $key, string $default, string $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getString($key, $default));
    }

    public static function providerGetString(): array {
        return [
            "int converted to string"    => [ [ "s" => 5 ], "s", "", "5" ],
            "plain string"               => [ [ "str" => "hello" ], "str", "", "hello" ],
            "numeric string preserved"   => [ [ "num" => "3" ], "num", "", "3" ],
            "null returns default"       => [ [ "null" => null ], "null", "", "" ],
            "missing key custom default" => [ [ "s" => 5 ], "missing", "def", "def" ],
            "non scalar value fallback"  => [ [ "arr" => [ "x" ] ], "arr", "fallback", "fallback" ],
            "enum key"                   => [ [ "Key" => "value" ], TestDictionaryEnum::Key, "", "value" ],
        ];
    }


    #[DataProvider("providerGetDate")]
    public function testGetDate(mixed $input, Enum|int|string $key, bool $shouldBeEmpty, int|null $expectedNumber): void {
        $d = new Dictionary($input);
        $date = $d->getDate($key);
        $this->assertInstanceOf(Date::class, $date);

        if ($shouldBeEmpty) {
            $this->assertTrue($date->isEmpty());
        } else {
            $this->assertTrue($date->isNotEmpty());
            if ($expectedNumber !== null) {
                $this->assertEquals($expectedNumber, $date->toNumber());
            }
        }
    }

    public static function providerGetDate(): array {
        $orig = Date::create("2019-12-31");
        $ts   = strtotime("2021-03-04");
        return [
            "date string"      => [ [ "date" => "2020-01-02" ], "date", false, 20200102 ],
            "timestamp int"    => [ [ "ts" => $ts ], "ts", false, null ],
            "date instance"    => [ [ "d" => $orig ], "d", false, null ],
            "missing key"      => [ [], "no", true, null ],
            "non scalar value" => [ [ "arr" => [ 1 ] ], "arr", true, null ],
            "invalid date"     => [ [ "date" => "not a date" ], "date", true, null ],
            "enum key"         => [ [ "Key" => "2020-01-02" ], TestDictionaryEnum::Key, false, 20200102 ],
        ];
    }


    #[DataProvider("providerGetDateParsed")]
    public function testGetDateParsed(mixed $input, Enum|int|string $key, bool $shouldBeEmpty, int|null $expectedNumber): void {
        $d = new Dictionary($input);
        $date = $d->getDateParsed($key);
        $this->assertInstanceOf(Date::class, $date);

        if ($shouldBeEmpty) {
            $this->assertTrue($date->isEmpty());
        } else {
            $this->assertTrue($date->isNotEmpty());
            if ($expectedNumber !== null) {
                $this->assertEquals($expectedNumber, $date->toNumber());
            }
        }
    }

    public static function providerGetDateParsed(): array {
        return [
            "common textual date" => [ [ "date" => "2/1/2020" ], "date", false, 20200102 ],
            "missing key"         => [ [], "no", true, null ],
            "invalid text"        => [ [ "bad" => "not a date" ], "bad", true, null ],
            "enum key"            => [ [ "Key" => "2/1/2020" ], TestDictionaryEnum::Key, false, 20200102 ],
        ];
    }


    #[DataProvider("providerGetKeys")]
    public function testGetKeys(mixed $input, array $expectedKeys): void {
        $d = new Dictionary($input);
        $keys = $d->getKeys();

        if (count($expectedKeys) === 0) {
            $this->assertEmpty($keys);
            return;
        }

        foreach ($expectedKeys as $expectedKey) {
            $this->assertContains($expectedKey, $keys);
        }
        foreach ($keys as $k) {
            $this->assertIsString($k);
        }
    }

    public static function providerGetKeys(): array {
        return [
            "associative array" => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ] ],
            "empty dictionary"  => [ [], [] ],
            "list style data"   => [ [ "x", "y" ], [ "0", "1" ] ],
            "mixed keys"        => [ [ 0 => "zero", "one" => 1 ], [ "0", "one" ] ],
        ];
    }


    #[DataProvider("providerGetDict")]
    public function testGetDict(mixed $input, Enum|int|string $key, int|string $intKey, bool $shouldBeEmpty, mixed $expectedValue): void {
        $d = new Dictionary($input);
        $result = $d->getDict($key);
        $this->assertInstanceOf(Dictionary::class, $result);

        if ($shouldBeEmpty) {
            $this->assertTrue($result->isEmpty());
        } else {
            $this->assertEquals($expectedValue, $result->get($intKey));
        }
    }

    public static function providerGetDict(): array {
        $orig = new Dictionary([ "x" => 2 ]);
        return [
            "nested_array"    => [ [ "sub" => [ "x" => 1 ]], "sub", "x", false, 1 ],
            "missing_key"     => [ [], "no", 0, true, null ],
            "dict_instance"   => [ [ "sub" => $orig ], "sub", "x", false, 2 ],
            "scalar_value"    => [ [ "x" => "string" ], "x", 0, false, "string" ],
            "list_elem_valid" => [ [[ "id" => "x" ]], 0, "id", false, "x" ],
            "list_elem_oob"   => [ [[ "id" => "x" ]], 1, "id", true, null ],
            "enum_key"        => [ [ "Key" => [ "x" => 1 ]], TestDictionaryEnum::Key, "x", false, 1 ],
        ];
    }


    #[DataProvider("providerFindDict")]
    public function testFindDict(mixed $input, Enum|int|string $key, mixed $searchValue, bool $shouldBeEmpty, mixed $expectedValue): void {
        $d = new Dictionary($input);
        $found = $d->findDict($key, $searchValue);
        $this->assertInstanceOf(Dictionary::class, $found);

        if ($shouldBeEmpty) {
            $this->assertTrue($found->isEmpty());
        } else {
            $this->assertEquals($expectedValue, $found->get($expectedValue === null ? null : "val"));
        }
    }

    public static function providerFindDict(): array {
        return [
            "basic find"        => [ [[ "id" => "a", "val" => 1 ], [ "id" => "b", "val" => 2 ], [ "noId" => 9 ]], "id", "b", false, 2 ],
            "missing value"     => [ [[ "id" => "a", "val" => 1 ], [ "id" => "b", "val" => 2 ]], "id", "z", true, null ],
            "missing key"       => [ [[ "id" => "a", "val" => 1 ]], "other", "a", true, null ],
            "top level map"     => [ [ "a" => [ "id" => "x", "val" => 10 ]], "id", "x", true, null ],
            "multiple matches"  => [ [[ "id" => "d", "val" => 4 ], [ "id" => "d", "val" => 5 ]], "id", "d", false, 4 ],
            "enum key"          => [ [[ "Key" => "a", "val" => 1 ], [ "Key" => "b", "val" => 2 ]], TestDictionaryEnum::Key, "b", false, 2 ],
            "scalar elements"   => [ [ "a", "b" ], "id", "a", true, null ],
            "empty dictionary"  => [ [], "id", "a", true, null ],

            // The value is typed as a string and compared with ===, so an
            // element holding the number is not the one it is looking for
            "int is not string" => [ [[ "id" => 1, "val" => 1 ]], "id", "1", true, null ],
            "string is string"  => [ [[ "id" => "1", "val" => 1 ]], "id", "1", false, 1 ],
        ];
    }


    #[DataProvider("providerGetList")]
    public function testGetList(mixed $input, Enum|int|string $key, array $expectedList, bool $shouldBeEmpty, mixed $expectedId): void {
        $d = new Dictionary($input);
        $list = $d->getList($key);
        $this->assertIsArray($list);

        if ($shouldBeEmpty) {
            $this->assertEmpty($list);
        } else {
            $this->assertCount(count($expectedList), $list);
            foreach ($list as $item) {
                $this->assertInstanceOf(Dictionary::class, $item);
            }
            if ($expectedId !== null) {
                $this->assertEquals($expectedId, $list[0]->get("id"));
            }
        }
    }

    public static function providerGetList(): array {
        $obj = new stdClass();
        $obj->p = "v";

        return [
            "proper nested list"    => [ [ "items" => [[ "id" => "a" ], [ "id" => "b" ]] ], "items", [[ "id" => "a" ], [ "id" => "b" ]], false, "a" ],
            "missing key"           => [ [], "no", [], true, null ],
            "non array value"       => [ [ "not" => "a string" ], "not", [], true, null ],
            "mixed list"            => [ [ "mixed" => [[ "id" => "x" ], "plain"] ], "mixed", [[ "id" => "x" ], "plain"], false, "x" ],
            "empty nested list"     => [ [ "empty" => [] ], "empty", [], true, null ],
            "objects in list"       => [ [ "objs" => [ $obj ] ], "objs", [ $obj ], false, null ],
            "nested arrays in list" => [ [ "items" => [[ "sub" => [ "a", "b" ], "vals" => [ 1, 2 ] ]]], "items", [[ "sub" => [ "a", "b" ], "vals" => [ 1, 2 ] ]], false, null ],
            "enum key"              => [ [ "Key" => [[ "id" => "a" ], [ "id" => "b" ]] ], TestDictionaryEnum::Key, [[ "id" => "a" ], [ "id" => "b" ]], false, "a" ],
        ];
    }


    #[DataProvider("providerGetFirst")]
    public function testGetFirst(mixed $input, Enum|int|string $key, bool $shouldBeEmpty, mixed $expectedValue): void {
        $d = new Dictionary($input);
        $result = $d->getFirst($key);
        $this->assertInstanceOf(Dictionary::class, $result);

        if ($shouldBeEmpty) {
            $this->assertTrue($result->isEmpty());
        } else {
            $this->assertEquals($expectedValue, $result->get($key === "" ? "id" : "n"));
        }
    }

    public static function providerGetFirst(): array {
        return [
            "top level list"   => [ [[ "id" => "a" ], [ "id" => "b" ]], "", false, "a" ],
            "nested list"      => [ [ "group" => [[ "n" => 1 ], [ "n" => 2 ]]], "group", false, 1 ],
            "empty dictionary" => [ [], "", true, null ],
            "enum key"         => [ [ "Key" => [[ "n" => 1 ], [ "n" => 2 ]] ], TestDictionaryEnum::Key, false, 1 ],
        ];
    }


    #[DataProvider("providerGetLast")]
    public function testGetLast(mixed $input, Enum|int|string $key, bool $shouldBeEmpty, mixed $expectedValue): void {
        $d = new Dictionary($input);
        $result = $d->getLast($key);
        $this->assertInstanceOf(Dictionary::class, $result);

        if ($shouldBeEmpty) {
            $this->assertTrue($result->isEmpty());
        } else {
            $this->assertEquals($expectedValue, $result->get($key === "" ? "id" : "n"));
        }
    }

    public static function providerGetLast(): array {
        return [
            "top level list"   => [ [[ "id" => "a" ], [ "id" => "b" ]], "", false, "b" ],
            "nested list"      => [ [ "group" => [[ "n" => 1 ], [ "n" => 2 ]]], "group", false, 2 ],
            "empty dictionary" => [ [], "", true, null ],
            "enum key"         => [ [ "Key" => [[ "n" => 1 ], [ "n" => 2 ]] ], TestDictionaryEnum::Key, false, 2 ],
        ];
    }


    #[DataProvider("providerGetInts")]
    public function testGetInts(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getInts($key));
    }

    public static function providerGetInts(): array {
        return [
            "basic ints"       => [ [ "ints" => [ "1", 2, "3" ]], "ints", [ 1, 2, 3 ] ],
            "missing key"      => [ [], "no", [] ],
            "non list value"   => [ [ "ints" => "not an array" ], "ints", [] ],
            "decimals rounded" => [ [ "ints" => [ "2.5", "-1.2" ]], "ints", [ 3, -1 ] ],
            "enum key"         => [ [ "Key" => [ "1", 2 ]], TestDictionaryEnum::Key, [ 1, 2 ] ],
            "integer key"      => [ [[ "1", 2 ]], 0, [ 1, 2 ] ],
            "dictionary value" => [ [ "ints" => new Dictionary([ 1, 2 ]) ], "ints", [ 1, 2 ] ],
        ];
    }


    #[DataProvider("providerGetStrings")]
    public function testGetStrings(mixed $input, Enum|int|string $key, bool $skipEmpty, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getStrings($key, $skipEmpty));
    }

    public static function providerGetStrings(): array {
        return [
            "basic strings"      => [ [ "strings" => [ "a", "", "b" ]], "strings", false, [ "a", "", "b" ] ],
            "skip empty strings" => [ [ "strings" => [ "a", "", "b" ]], "strings", true, [ "a", "b" ] ],
            "missing key"        => [ [], "no", false, [] ],
            "non list value"     => [ [ "strings" => "not an array" ], "strings", false, [ "not an array" ] ],
            "numeric to strings" => [ [ "strings" => [ 1, 2 ]], "strings", false, [ "1", "2" ] ],
            "enum key"           => [ [ "Key" => [ "1", 2 ]], TestDictionaryEnum::Key, false, [ "1", "2" ] ],
            "integer key"        => [ [[ "a", "b" ]], 0, false, [ "a", "b" ] ],
            "dictionary value"   => [ [ "strings" => new Dictionary([ "k", "v" ]) ], "strings", false, [ "k", "v" ] ],
        ];
    }


    #[DataProvider("providerGetArray")]
    public function testGetArray(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getArray($key));
    }

    public static function providerGetArray(): array {
        return [
            "basic array"        => [ [ "arr" => [ "x" ]], "arr", [ "x" ] ],
            "missing key"        => [ [], "no", [] ],
            "non array value"    => [ [ "arr" => "str" ], "arr", [] ],
            "array mixed values" => [ [ "arr" => [ 1, "x" ]], "arr", [ 1, "x" ] ],
            "enum key"           => [ [ "Key" => [ "x" ]], TestDictionaryEnum::Key, [ "x" ] ],
            "integer key"        => [ [[ "x" ], [ "y" ]], 1, [ "y" ] ],
        ];
    }


    #[DataProvider("providerGetJSON")]
    public function testGetJSON(mixed $input, Enum|int|string $key, string $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getJSON($key));
    }

    public static function providerGetJSON(): array {
        return [
            "basic json"      => [ [ "json" => [ "k" => "v" ]], "json", '{"k":"v"}' ],
            "missing key"     => [ [], "no", "[]" ],
            "non array value" => [ [ "json" => "not an array" ], "json", "[]" ],
            "invalid input"   => [ "not json", "any", "[]" ],
            "enum key"        => [ [ "Key" => [ "k" => "v" ]], TestDictionaryEnum::Key, '{"k":"v"}' ],
            "integer key"     => [ [[ "k" => "v" ]], 0, '{"k":"v"}' ],
        ];
    }


    #[DataProvider("providerDecodeAsArray")]
    public function testDecodeAsArray(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->decodeAsArray($key));
    }

    public static function providerDecodeAsArray(): array {
        return [
            "json object"  => [ [ "jsonObj" => '{"k":"v"}' ], "jsonObj", [ "k" => "v" ] ],
            "json array"   => [ [ "jsonArr" => '["x", 2]' ], "jsonArr", [ "x", 2 ] ],
            "invalid json" => [ [ "bad" => "not json" ], "bad", [] ],
            "missing key"  => [ [], "missing", [] ],
            "enum key"     => [ [ "Key" => '{"k":"v"}' ], TestDictionaryEnum::Key, [ "k" => "v" ] ],
            "integer key"  => [ [ '{"k":"v"}' ], 0, [ "k" => "v" ] ],
        ];
    }


    #[DataProvider("providerDecodeAsStrings")]
    public function testDecodeAsStrings(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->decodeAsStrings($key));
    }

    public static function providerDecodeAsStrings(): array {
        return [
            "json strings"  => [ [ "jsonStr" => '["a","b"]' ], "jsonStr", [ "a", "b" ] ],
            "json numerics" => [ [ "jsonNum" => '[1,2]' ], "jsonNum", [ "1", "2" ] ],
            "json empty"    => [ [ "jsonEmpty" => '[]' ], "jsonEmpty", [] ],
            "invalid json"  => [ [ "bad" => "not json" ], "bad", [] ],
            "enum key"      => [ [ "Key" => '["a","b"]' ], TestDictionaryEnum::Key, [ "a", "b" ] ],
            "integer key"   => [ [ '["a","b"]' ], 0, [ "a", "b" ] ],
            "missing key"   => [ [], "missing", [] ],
        ];
    }


    // Asserted by what the map holds rather than by which keys it has, since
    // the values could have been taken from the wrong place and still pass

    #[DataProvider("providerCreateMap")]
    public function testCreateMap(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->createMap($key)->toArray());
    }

    public static function providerCreateMap(): array {
        return [
            "basic map"                => [ [[ "id" => "x", "v" => 1 ], [ "id" => "y", "v" => 2 ]], "id", [ "x" => [ "id" => "x", "v" => 1 ], "y" => [ "id" => "y", "v" => 2 ]] ],
            "missing key skipped"      => [ [[ "id" => "x", "v" => 1 ], [ "v" => 2 ]], "id", [ "x" => [ "id" => "x", "v" => 1 ]] ],
            "empty key skipped"        => [ [[ "id" => "", "v" => 1 ], [ "id" => "y", "v" => 2 ]], "id", [ "y" => [ "id" => "y", "v" => 2 ]] ],
            "numeric keys stringified" => [ [[ "id" => 1, "v" => "a" ]], "id", [ "1" => [ "id" => 1, "v" => "a" ]] ],
            "enum key"                 => [ [[ "Key" => "x", "v" => 1 ]], TestDictionaryEnum::Key, [ "x" => [ "Key" => "x", "v" => 1 ]] ],
            "last one wins"            => [ [[ "id" => "x", "v" => 1 ], [ "id" => "x", "v" => 2 ]], "id", [ "x" => [ "id" => "x", "v" => 2 ]] ],
            "scalars skipped"          => [ [[ "id" => "x" ], "plain", 5 ], "id", [ "x" => [ "id" => "x" ]] ],
            "needs a list"             => [ [ "id" => "x", "v" => 1 ], "id", [] ],
            "empty dictionary"         => [ [], "id", [] ],
            "invalid input"            => [ "", "id", [] ],
        ];
    }


    // The second key was never given, so nothing had ever read the branch that
    // takes one value out of each element rather than the whole of it

    #[DataProvider("providerCreateMapWithValue")]
    public function testCreateMapWithValue(mixed $input, Enum|int|string $key, Enum|int|string $value, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->createMap($key, $value)->toArray());
    }

    public static function providerCreateMapWithValue(): array {
        return [
            "basic map"           => [ [[ "id" => "x", "v" => 1 ], [ "id" => "y", "v" => 2 ]], "id", "v", [ "x" => 1, "y" => 2 ] ],
            "missing value"       => [ [[ "id" => "x", "v" => 1 ], [ "id" => "y" ]], "id", "v", [ "x" => 1 ] ],
            "null value skipped"  => [ [[ "id" => "x", "v" => null ], [ "id" => "y", "v" => 2 ]], "id", "v", [ "y" => 2 ] ],
            "empty value kept"    => [ [[ "id" => "x", "v" => "" ]], "id", "v", [ "x" => "" ] ],
            "array value"         => [ [[ "id" => "x", "v" => [ 1, 2 ]]], "id", "v", [ "x" => [ 1, 2 ]] ],
            "missing key skipped" => [ [[ "v" => 1 ], [ "id" => "y", "v" => 2 ]], "id", "v", [ "y" => 2 ] ],
            "enum key and value"  => [ [[ "Key" => "x", "Value" => 1 ]], TestDictionaryEnum::Key, TestDictionaryEnum::Value, [ "x" => 1 ] ],
            "same key twice"      => [ [[ "id" => "x" ]], "id", "id", [ "x" => "x" ] ],
            "needs a list"        => [ [ "id" => "x", "v" => 1 ], "id", "v", [] ],
            "empty dictionary"    => [ [], "id", "v", [] ],
        ];
    }


    #[DataProvider("providerToArray")]
    public function testToArray(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toArray());
    }

    public static function providerToArray(): array {
        return [
            "basic array"         => [ [ "a" => "1", "b" => 2, "c" => [ "x" ]], [ "a" => "1", "b" => 2, "c" => [ "x" ]] ],
            "nested dictionaries" => [ [ "sub" => [ "k" => "v" ]], [ "sub" => [ "k" => "v" ]] ],
            "invalid input"       => [ "", [] ],
        ];
    }


    #[DataProvider("providerToList")]
    public function testToList(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toList());
    }

    public static function providerToList(): array {
        return [
            "list style data"     => [ [ 1, "x" ], [ 1, "x" ] ],
            "associative map"     => [ [ "a" => "1", "b" => "2" ], [ "1", "2" ] ],
            "nested dictionaries" => [ [[ "k" => "v" ]], [[ "k" => "v" ]] ],
            "invalid input"       => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStrings")]
    public function testToStrings(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStrings());
    }

    public static function providerToStrings(): array {
        return [
            "basic strings" => [ [ "a" => "1", "b" => "", "c" => 3 ], [ "1", "", "3" ] ],
            "nested arrays" => [ [ "x" => [ 1 ], "y" => null ], [ "", "" ] ],
            "invalid input" => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringsMap")]
    public function testToStringsMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringsMap());
    }

    public static function providerToStringsMap(): array {
        return [
            "basic strings map" => [ [ "a" => 1, "b" => "", "c" => null ], [ "a" => "1", "b" => "", "c" => "" ] ],
            "numeric keys"      => [ [ "id", 3, null ], [ "id", "3", "" ] ],
            "invalid input"     => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringIntMap")]
    public function testToStringIntMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringIntMap());
    }

    public static function providerToStringIntMap(): array {
        return [
            "basic string int map" => [ [ "a" => 1, "b" => "2" ], [ "a" => 1, "b" => 2 ] ],
            "invalid input"        => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringMixedMap")]
    public function testToStringMixedMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringMixedMap());
    }

    public static function providerToStringMixedMap(): array {
        return [
            "basic string mixed map" => [ [ "a" => 1, "b" => "s" ], [ "a" => 1, "b" => "s" ] ],
            "nested dictionaries"    => [ [ "sub" => [ "k" => "v" ]], [ "sub" => [ "k" => "v" ]] ],
            "invalid input"          => [ "", [] ],
        ];
    }


    #[DataProvider("providerToInts")]
    public function testToInts(mixed $input, bool $withoutEmpty, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toInts($withoutEmpty));
    }

    public static function providerToInts(): array {
        return [
            "list of values"        => [ [ "1", 2, "3", -4.4 ], false, [ 1, 2, 3, -4 ] ],
            "skip empty strings"    => [ [ "a" => "1", "b" => "", "c" => "2.5" ], true, [ 1, 3 ] ],
            "include empty strings" => [ [ "a" => "1", "b" => "0", "c" => "2.5" ], false, [ 1, 0, 3 ] ],
            "negative decimal"      => [ [ "n" => "-1.6", "p" => "2.4" ], false, [ -2, 2 ] ],
            "invalid input"         => [ "", true, [] ],
        ];
    }


    #[DataProvider("providerToIntsMap")]
    public function testToIntsMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toIntsMap());
    }

    public static function providerToIntsMap(): array {
        return [
            "basic ints map" => [ [ "1" => "2", "3" => 4 ], [ 1 => 2, 3 => 4 ] ],
            "float like"     => [ [ "1.2" => "3.4" ], [ 1 => 3 ] ],
            "null values"    => [ [ "1" => null ], [ 1 => 0 ] ],
            "invalid input"  => [ "", [] ],
        ];
    }


    #[DataProvider("providerToIntFloatMap")]
    public function testToIntFloatMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toIntFloatMap());
    }

    public static function providerToIntFloatMap(): array {
        return [
            "basic int float map" => [ [ "1" => "2.5", "3" => 4 ], [ 1 => 2.5, 3 => 4.0 ] ],
            "float like keys"     => [ [ "1.2" => "2.5" ], [ 1 => 2.5 ] ],
            "null values"         => [ [ "1" => null ], [ 1 => 0.0 ] ],
            "invalid input"       => [ "", [] ],
        ];
    }


    #[DataProvider("providerToIntStringMap")]
    public function testToIntStringMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toIntStringMap());
    }

    public static function providerToIntStringMap(): array {
        return [
            "basic int string map" => [ [ "1" => "one", "2" => "two" ], [ "1" => "one", "2" => "two" ] ],
            "invalid input"        => [ "", [] ],
        ];
    }


    #[DataProvider("providerToJSON")]
    public function testToJSON(mixed $input, array $expectedKeys, bool $shouldBeEmpty): void {
        $d       = new Dictionary($input);
        $json    = $d->toJSON();
        $decoded = json_decode($json, true);

        $this->assertIsString($json);
        $this->assertIsArray($decoded);

        if ($shouldBeEmpty) {
            $this->assertEmpty($decoded);
        } else {
            foreach ($expectedKeys as $expectedKey) {
                $this->assertArrayHasKey($expectedKey, $decoded);
            }
        }
    }

    public static function providerToJSON(): array {
        return [
            "basic json"    => [ [ "a" => "1", "b" => "" ], [ "a", "b" ], false ],
            "invalid input" => [ "", [], true ],
        ];
    }


    #[DataProvider("providerCount")]
    public function testCount(mixed $input, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->count());
    }

    public static function providerCount(): array {
        return [
            "associative array" => [ [ "a" => 1, "b" => 2 ], 2 ],
            "empty dictionary"  => [ [], 0 ],
            "list style data"   => [ [ "x", "y", "z" ], 3 ],
            "invalid input"     => [ "", 0 ],
        ];
    }


    #[DataProvider("providerIterator")]
    public function testIterator(mixed $input, array $expectedKeys, int $expectedCount): void {
        $d = new Dictionary($input);

        // The foreach below is what calls it, and asking for it is what names it
        $this->assertInstanceOf(Traversable::class, $d->getIterator());

        $collected = [];
        foreach ($d as $k => $v) {
            $this->assertInstanceOf(Dictionary::class, $v);
            $collected[] = $k;
        }
        $this->assertEquals($expectedCount, count($collected));
        foreach ($expectedKeys as $expectedKey) {
            $this->assertContains($expectedKey, $collected);
        }
    }

    public static function providerIterator(): array {
        return [
            "basic iteration"  => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], 2 ],
            "list of arrays"   => [ [[ "id" => 1 ], [ "id" => 2 ]], [ "0", "1" ], 2 ],
            "list of scalars"  => [ [ "x", "y" ], [ "0", "1" ], 2 ],
            "nested arrays"    => [ [ "a" => [ "x" => 1 ] ], [ "a" ], 1 ],
            "null value"       => [ [ "a" => null ], [ "a" ], 1 ],
            "empty dictionary" => [ [], [], 0 ],
            "invalid input"    => [ "", [], 0 ],
        ];
    }


    // Every value goes back through the constructor, so a scalar comes out
    // listed and anything it cannot read comes out empty

    #[DataProvider("providerIteratorValues")]
    public function testTheIteratorWrapsEveryValue(mixed $input, array $expected): void {
        $d      = new Dictionary($input);
        $result = [];
        foreach ($d as $key => $value) {
            $result[$key] = $value->toArray();
        }
        $this->assertEquals($expected, $result);
    }

    public static function providerIteratorValues(): array {
        return [
            "nested array"  => [ [ "a" => [ "x" => 1 ] ], [ "a" => [ "x" => 1 ]] ],
            "scalar string" => [ [ "a" => "plain" ], [ "a" => [ "plain" ]] ],
            "comma string"  => [ [ "a" => "x,y" ], [ "a" => [ "x", "y" ]] ],
            "integer"       => [ [ "a" => 5 ], [ "a" => []] ],
            "null"          => [ [ "a" => null ], [ "a" => []] ],
            "numeric keys"  => [ [ [ "x" => 1 ], [ "y" => 2 ] ], [ "0" => [ "x" => 1 ], "1" => [ "y" => 2 ]] ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(mixed $input, array $expectedKeys, bool $shouldBeEmpty, mixed $expectedValue): void {
        $d = new Dictionary($input);
        $serialized = $d->jsonSerialize();
        $this->assertIsArray($serialized);

        if ($shouldBeEmpty) {
            $this->assertEmpty($serialized);
        } else {
            foreach ($expectedKeys as $expectedKey) {
                $this->assertArrayHasKey($expectedKey, $serialized);
            }
            if ($expectedValue !== null) {
                $this->assertEquals($expectedValue, $serialized[$expectedKeys[0]]);
            }
        }

        // ensure it can be encoded to JSON
        $this->assertIsString(json_encode($serialized));
    }

    public static function providerJsonSerialize(): array {
        return [
            "basic array"         => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], false, 1 ],
            "nested dictionaries" => [ [ "sub" => [ "k" => "v" ]], [ "sub" ], false, null ],
            "invalid input"       => [ "", [], true, null ],
        ];
    }
}
