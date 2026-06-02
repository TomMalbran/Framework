<?php
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Dictionary;

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
            "simple_string"   => [ "simple string", 0, "simple string", false ],
            "invalid_integer" => [ 12345, 0, null, true ],
            "invalid_string"  => [ "", 0, null, true ],
        ];
    }


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(mixed $input, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"     => [ [], true ],
            "non_empty" => [ [ "a" => 1 ], false ],
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
            "non_empty" => [ [ "a" => 1 ], true ],
        ];
    }


    #[DataProvider("providerGetTotal")]
    public function testGetTotal(mixed $input, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getTotal());
    }

    public static function providerGetTotal(): array {
        return [
            "associative_array" => [ [ "a" => 1, "b" => 2 ], 2 ],
            "list_of_arrays"    => [ [[ "id" => "x" ], [ "id" => "y" ]], 2 ],
            "invalid_input"     => [ "", 0 ],
            "empty_dictionary"  => [ [], 0 ],
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
            "equal_dictionaries"   => [ [ "k" => "v" ], [ "k" => "v" ], true ],
            "unequal_dictionaries" => [ [ "k" => "v" ], [ "k" => "x" ], false ],
            "invalid_input"        => [ "not json", [ "k" => "v" ], false ],
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
            "unequal_dictionaries" => [ [ "k" => "v" ], [ "k" => "x" ], true ],
            "equal_dictionaries"   => [ [ "k" => "v" ], [ "k" => "v" ], false ],
            "invalid_input"        => [ "", [ "k" => "v" ], true ],
        ];
    }


    #[DataProvider("providerIsList")]
    public function testIsList(mixed $input, string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isList($key));
    }

    public static function providerIsList(): array {
        return [
            "top_level_list" => [ [ 1, 2, 3 ], "", true ],
            "nested_list"    => [ [ "list" => [ "a", "b" ] ], "list", true ],
            "non_list_array" => [ [ "a" => "b" ], "", false ],
            "invalid_input"  => [ "", "", false ],
        ];
    }


    #[DataProvider("providerIsArrayList")]
    public function testIsArrayList(mixed $input, string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->isArrayList($key));
    }

    public static function providerIsArrayList(): array {
        return [
            "top_level_array_list" => [ [[ "a" => 1 ], [ "a" => 2 ]], "", true ],
            "nested_array_list"    => [ [ "key" => [[ "x" => 1 ]] ], "key", true ],
            "non_numeric_keys"     => [ [ "a" => [ "x" => 1 ] ], "a", false ],
            "invalid_input"        => [ "not json", "", false ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->has($key));
    }

    public static function providerHas(): array {
        return [
            "associative_key_exists"  => [ [ "a" => 1 ], "a", true ],
            "associative_key_missing" => [ [ "a" => 1 ], "missing", false ],
            "numeric_key_exists"      => [ [ "x", "y" ], 0, true ],
            "numeric_key_missing"     => [ [ "x", "y" ], 2, false ],
            "enum_key_exists"         => [ [ "Key" => "value" ], TestDictionaryEnum::Key, true ],
            "enum_key_missing"        => [ [ "Key" => "value" ], TestDictionaryEnum::Value, false ],
            "zero_value"              => [ [ "k" => 0 ], "k", true ],
            "empty_string_value"      => [ [ "k2" => "" ], "k2", true ],
            "invalid_input"           => [ "not json", "any", false ],
        ];
    }


    #[DataProvider("providerHasValue")]
    public function testHasValue(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->hasValue($key));
    }

    public static function providerHasValue(): array {
        return [
            "assoc_empty_string"    => [ [ "a" => "" ], "a", false ],
            "assoc_non_empty"       => [ [ "b" => "x" ], "b", true ],
            "assoc_zero"            => [ [ "c" => 0 ], "c", false ],
            "assoc_key_missing"     => [ [ "a" => "x" ], "missing", false ],
            "numeric_key_empty"     => [ [ "", "y" ], 0, false ],
            "numeric_key_non_empty" => [ [ "", "y" ], 1, true ],
            "enum_key_empty"        => [ [ "Key" => "" ], TestDictionaryEnum::Key, false ],
            "enum_key_non_empty"    => [ [ "Key" => "value" ], TestDictionaryEnum::Key, true ],

            "list_first_empty"      => [ [ "", "y", 0 ], 0, false ],
            "list_second_non_empty" => [ [ "", "y", 0 ], 1, true ],
            "list_third_zero"       => [ [ "", "y", 0 ], 2, false ],

            "invalid_input"         => [ "not json", "any", false ],
        ];
    }


    #[DataProvider("providerContains")]
    public function testContains(mixed $input, int|string|null $key, mixed $value, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->contains($value, $key));
    }

    public static function providerContains(): array {
        return [
            "list_string_found"     => [ [ "x", "y" ], null, "x", true ],
            "list_string_not_found" => [ [ "x", "y" ], null, "z", false ],
            "list_int_found"        => [ [ 1, 2, 3 ], null, 2, true ],
            "list_int_not_found"    => [ [ 1, 2, 3 ], null, 4, false ],
            "list_of_dicts_found"   => [ [[ "id" => "x" ], [ "id" => "y" ]], "id", "x", true ],
            "assoc_key_found"       => [ [ "k" => 1 ], null, "k", true ],
            "assoc_key_not_found"   => [ [ "k" => 1 ], null, "missing", false ],
            "invalid_input"         => [ "not json", null, "any", false ],
        ];
    }


    #[DataProvider("providerContainsInt")]
    public function testContainsInt(mixed $input, int $value, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->containsInt($value));
    }

    public static function providerContainsInt(): array {
        return [
            "list_int_found"           => [ [ 1, 2, 3 ], 2, true ],
            "list_int_not_found"       => [ [ 1, 2, 3 ], 4, false ],
            "string_key_int_found"     => [ [ "2" => "v" ], 2, true ],
            "string_key_int_not_found" => [ [ "2" => "v" ], 3, false ],
            "invalid_input"            => [ "not json", 1, false ],
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
            "new_key"       => [ [ "x" => 1 ], [ "y" => 2 ], "y", 2, 2 ],
            "overwrite_key" => [ [ "x" => 1, "y" => 2 ], [ "x" => 10 ], "x", 10, 2 ],
            "empty_dict"    => [ [ "x" => 1, "y" => 2 ], [], "x", 1, 2 ],
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
            "scalar_to_list"      => [ [ 1 ], 2, [ 1, 2 ], true ],
            "dict_to_list"        => [ [[ "a" => 1 ]], new Dictionary([ "b" => 2 ]), [[ "a" => 1 ], [ "b" => 2 ]], true ],
            "to_assoc_map"        => [ [ "k" => "v" ], "new", [], false ],
            "array_to_assoc_map"  => [ [ "k" => "v" ], [ "new" ], [], false ],
            "to_empty_dictionary" => [ [], "first", [ "first" ], true ],
            "to_invalid_input"    => [ "", "value", [ "value" ], true ],
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
            "string_value" => [ [], "k", "v" ],
            "numeric_key"  => [ [], 0, "zero" ],
            "enum_key"     => [ [], TestDictionaryEnum::Key, "value" ],
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
            "string_key"  => [ "s", "str" ],
            "numeric_key" => [ 0, "zero" ],
            "enum_key"    => [ TestDictionaryEnum::Key, "value" ],
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
            "string_key"  => [ "n", 5 ],
            "numeric_key" => [ 0, 100 ],
            "enum_key"    => [ TestDictionaryEnum::Key, 10 ],
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
            "string_key"  => [ "e", TestDictionaryEnum::Value ],
            "numeric_key" => [ 0, TestDictionaryEnum::Value ],
            "enum_key"    => [ TestDictionaryEnum::Key, TestDictionaryEnum::Value ],
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
            "associative_key"  => [ [ "a" => 1 ], "a", false, 0 ],
            "missing_key"      => [ [ "a" => 1 ], "missing", false, 1 ],
            "numeric_key_list" => [ [ "first", "second", "third" ], 1, false, 2 ],
            "enum_key"         => [ [ "Key" => "value" ], TestDictionaryEnum::Key, false, 0 ],
        ];
    }


    #[DataProvider("providerGet")]
    public function testGet(mixed $input, Enum|int|string $key, mixed $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->get($key));
    }

    public static function providerGet(): array {
        return [
            "string_value"  => [ [ "x" => "y" ], "x", "y" ],
            "missing_key"   => [ [ "x" => "y" ], "none", null ],
            "numeric_key"   => [ [ "first", "second" ], 0, "first" ],
            "enum_key"      => [ [ "Key" => "value" ], TestDictionaryEnum::Key, "value" ],
            "array_value"   => [ [ "arr" => [ "a" => 1 ]], "arr", [ "a" => 1 ] ],
            "set_then_get"  => [ [], "new", null ],
            "invalid_input" => [ "not json", "any", null ],
        ];
    }


    #[DataProvider("providerGetBool")]
    public function testGetBool(mixed $input, Enum|int|string $key, bool $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getBool($key));
    }

    public static function providerGetBool(): array {
        return [
            "empty_string"   => [ [ "a" => "" ], "a", false ],
            "numeric_string" => [ [ "b" => "1" ], "b", true ],
            "zero_int"       => [ [ "c" => 0 ], "c", false ],
            "zero_string"    => [ [ "d" => "0" ], "d", false ],
            "true_bool"      => [ [ "e" => true ], "e", true ],
            "false_bool"     => [ [ "f" => false ], "f", false ],
            "true_string"    => [ [ "g" => "true" ], "g", true ],
            "false_string"   => [ [ "h" => "false" ], "h", true ],
            "array_value"    => [ [ "arr" => [ 1 ] ], "arr", false ],
            "enum_key"       => [ [ "Key" => true ], TestDictionaryEnum::Key, true ],
            "missing_key"    => [ [], "missing", false ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(mixed $input, Enum|int|string $key, int $decimals, int $default, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getInt($key, $decimals, $default));
    }

    public static function providerGetInt(): array {
        return [
            "rounding_up"         => [ [ "n" => "3.7" ], "n", 0, 0, 4 ],
            "with_decimals"       => [ [ "n" => "3.7" ], "n", 1, 0, 37 ],
            "integer_string"      => [ [ "sint" => "3" ], "sint", 0, 0, 3 ],
            "negative_rounding"   => [ [ "neg" => "-2.4" ], "neg", 0, 0, -2 ],
            "decimals_scaling"    => [ [ "x" => "3.456" ], "x", 2, 0, 346 ],
            "missing_key_default" => [ [], "missing", 0, 0, 0 ],
            "missing_key_custom"  => [ [], "missing", 0, 7, 7 ],
            "non_scalar_default"  => [ [ "bad" => [ 1 ] ], "bad", 0, 5, 5 ],
            "enum_key"            => [ [ "Key" => "3.2" ], TestDictionaryEnum::Key, 0, 0, 3 ],
        ];
    }


    #[DataProvider("providerGetFloat")]
    public function testGetFloat(mixed $input, Enum|int|string $key, float $default, float $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getFloat($key, $default));
    }

    public static function providerGetFloat(): array {
        return [
            "numeric_string"     => [ [ "f" => "2.5" ], "f", 0.0, 2.5 ],
            "integer"            => [ [ "i" => 3 ], "i", 0.0, 3.0 ],
            "negative_float"     => [ [ "neg" => "-1.25" ], "neg", 0.0, -1.25 ],
            "missing_key"        => [ [], "missing", 0.0, 0.0 ],
            "missing_key_custom" => [ [], "missing", 1.23, 1.23 ],
            "non_scalar_default" => [ [ "arr" => [ 1 ] ], "arr", 9.9, 9.9 ],
            "enum_key"           => [ [ "Key" => "3.2" ], TestDictionaryEnum::Key, 0.0, 3.2 ],
        ];
    }


    #[DataProvider("providerGetPrice")]
    public function testGetPrice(mixed $input, Enum|int|string $key, float $default, float $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getPrice($key, $default));
    }

    public static function providerGetPrice(): array {
        return [
            "cents_as_integer"    => [ [ "p" => 123 ], "p", 0.0, 1.23 ],
            "numeric_string"      => [ [ "p_str" => "150" ], "p_str", 0.0, 150.0 ],
            "zero_cents"          => [ [ "p_zero" => 0 ], "p_zero", 0.0, 0.0 ],
            "negative_cents"      => [ [ "neg" => -250 ], "neg", 0.0, -2.50 ],
            "missing_key_default" => [ [], "missing", 0.0, 0.0 ],
            "missing_key_custom"  => [ [], "missing", 9.99, 9.99 ],
            "non_scalar_default"  => [ [ "arr" => [ 1 ] ], "arr", 5.50, 5.50 ],
            "enum_key"            => [ [ "Key" => 250 ], TestDictionaryEnum::Key, 0.0, 2.50 ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(mixed $input, Enum|int|string $key, string $default, string $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getString($key, $default));
    }

    public static function providerGetString(): array {
        return [
            "int_converted_to_string"    => [ [ "s" => 5 ], "s", "", "5" ],
            "plain_string"               => [ [ "str" => "hello" ], "str", "", "hello" ],
            "numeric_string_preserved"   => [ [ "num" => "3" ], "num", "", "3" ],
            "null_returns_default"       => [ [ "null" => null ], "null", "", "" ],
            "missing_key_custom_default" => [ [ "s" => 5 ], "missing", "def", "def" ],
            "non_scalar_value_fallback"  => [ [ "arr" => [ "x" ] ], "arr", "fallback", "fallback" ],
            "enum_key"                   => [ [ "Key" => "value" ], TestDictionaryEnum::Key, "", "value" ],
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
            "date_string"      => [ [ "date" => "2020-01-02" ], "date", false, 20200102 ],
            "timestamp_int"    => [ [ "ts" => $ts ], "ts", false, null ],
            "date_instance"    => [ [ "d" => $orig ], "d", false, null ],
            "missing_key"      => [ [], "no", true, null ],
            "non_scalar_value" => [ [ "arr" => [ 1 ] ], "arr", true, null ],
            "invalid_date"     => [ [ "date" => "not a date" ], "date", true, null ],
            "enum_key"         => [ [ "Key" => "2020-01-02" ], TestDictionaryEnum::Key, false, 20200102 ],
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
            "common_textual_date" => [ [ "date" => "2/1/2020" ], "date", false, 20200102 ],
            "missing_key"         => [ [], "no", true, null ],
            "invalid_text"        => [ [ "bad" => "not a date" ], "bad", true, null ],
            "enum_key"            => [ [ "Key" => "2/1/2020" ], TestDictionaryEnum::Key, false, 20200102 ],
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
            "associative_array" => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ] ],
            "empty_dictionary"  => [ [], [] ],
            "list_style_data"   => [ [ "x", "y" ], [ "0", "1" ] ],
            "mixed_keys"        => [ [ 0 => "zero", "one" => 1 ], [ "0", "one" ] ],
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
            "basic_find"       => [ [[ "id" => "a", "val" => 1 ], [ "id" => "b", "val" => 2 ], [ "noId" => 9 ]], "id", "b", false, 2 ],
            "missing_value"    => [ [[ "id" => "a", "val" => 1 ], [ "id" => "b", "val" => 2 ]], "id", "z", true, null ],
            "top_level_map"    => [ [ "a" => [ "id" => "x", "val" => 10 ]], "id", "x", true, null ],
            "multiple_matches" => [ [[ "id" => "d", "val" => 4 ], [ "id" => "d", "val" => 5 ]], "id", "d", false, 4 ],
            "enum_key"         => [ [[ "Key" => "a", "val" => 1 ], [ "Key" => "b", "val" => 2 ]], TestDictionaryEnum::Key, "b", false, 2 ],
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
            "proper_nested_list"    => [ [ "items" => [[ "id" => "a" ], [ "id" => "b" ]] ], "items", [[ "id" => "a" ], [ "id" => "b" ]], false, "a" ],
            "missing_key"           => [ [], "no", [], true, null ],
            "non_array_value"       => [ [ "not" => "a string" ], "not", [], true, null ],
            "mixed_list"            => [ [ "mixed" => [[ "id" => "x" ], "plain"] ], "mixed", [[ "id" => "x" ], "plain"], false, "x" ],
            "empty_nested_list"     => [ [ "empty" => [] ], "empty", [], true, null ],
            "objects_in_list"       => [ [ "objs" => [ $obj ] ], "objs", [ $obj ], false, null ],
            "nested_arrays_in_list" => [ [ "items" => [[ "sub" => [ "a", "b" ], "vals" => [ 1, 2 ] ]]], "items", [[ "sub" => [ "a", "b" ], "vals" => [ 1, 2 ] ]], false, null ],
            "enum_key"              => [ [ "Key" => [[ "id" => "a" ], [ "id" => "b" ]] ], TestDictionaryEnum::Key, [[ "id" => "a" ], [ "id" => "b" ]], false, "a" ],
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
            "top_level_list"   => [ [[ "id" => "a" ], [ "id" => "b" ]], "", false, "a" ],
            "nested_list"      => [ [ "group" => [[ "n" => 1 ], [ "n" => 2 ]]], "group", false, 1 ],
            "empty_dictionary" => [ [], "", true, null ],
            "enum_key"         => [ [ "Key" => [[ "n" => 1 ], [ "n" => 2 ]] ], TestDictionaryEnum::Key, false, 1 ],
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
            "top_level_list"   => [ [[ "id" => "a" ], [ "id" => "b" ]], "", false, "b" ],
            "nested_list"      => [ [ "group" => [[ "n" => 1 ], [ "n" => 2 ]]], "group", false, 2 ],
            "empty_dictionary" => [ [], "", true, null ],
            "enum_key"         => [ [ "Key" => [[ "n" => 1 ], [ "n" => 2 ]] ], TestDictionaryEnum::Key, false, 2 ],
        ];
    }


    #[DataProvider("providerGetInts")]
    public function testGetInts(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getInts($key));
    }

    public static function providerGetInts(): array {
        return [
            "basic_ints"       => [ [ "ints" => [ "1", 2, "3" ]], "ints", [ 1, 2, 3 ] ],
            "missing_key"      => [ [], "no", [] ],
            "non_list_value"   => [ [ "ints" => "not an array" ], "ints", [] ],
            "decimals_rounded" => [ [ "ints" => [ "2.5", "-1.2" ]], "ints", [ 3, -1 ] ],
            "enum_key"         => [ [ "Key" => [ "1", 2 ]], TestDictionaryEnum::Key, [ 1, 2 ] ],
            "integer_key"      => [ [[ "1", 2 ]], 0, [ 1, 2 ] ],
        ];
    }


    #[DataProvider("providerGetStrings")]
    public function testGetStrings(mixed $input, Enum|int|string $key, bool $skipEmpty, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getStrings($key, $skipEmpty));
    }

    public static function providerGetStrings(): array {
        return [
            "basic_strings"      => [ [ "strings" => [ "a", "", "b" ]], "strings", false, [ "a", "", "b" ] ],
            "skip_empty_strings" => [ [ "strings" => [ "a", "", "b" ]], "strings", true, [ "a", "b" ] ],
            "missing_key"        => [ [], "no", false, [] ],
            "non_list_value"     => [ [ "strings" => "not an array" ], "strings", false, [ "not an array" ] ],
            "numeric_to_strings" => [ [ "strings" => [ 1, 2 ]], "strings", false, [ "1", "2" ] ],
            "enum_key"           => [ [ "Key" => [ "1", 2 ]], TestDictionaryEnum::Key, false, [ "1", "2" ] ],
            "integer_key"        => [ [[ "a", "b" ]], 0, false, [ "a", "b" ] ],
        ];
    }


    #[DataProvider("providerGetArray")]
    public function testGetArray(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getArray($key));
    }

    public static function providerGetArray(): array {
        return [
            "basic_array"        => [ [ "arr" => [ "x" ]], "arr", [ "x" ] ],
            "missing_key"        => [ [], "no", [] ],
            "non_array_value"    => [ [ "arr" => "str" ], "arr", [] ],
            "array_mixed_values" => [ [ "arr" => [ 1, "x" ]], "arr", [ 1, "x" ] ],
            "enum_key"           => [ [ "Key" => [ "x" ]], TestDictionaryEnum::Key, [ "x" ] ],
            "integer_key"        => [ [[ "x" ], [ "y" ]], 1, [ "y" ] ],
        ];
    }


    #[DataProvider("providerGetJSON")]
    public function testGetJSON(mixed $input, Enum|int|string $key, string $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->getJSON($key));
    }

    public static function providerGetJSON(): array {
        return [
            "basic_json"      => [ [ "json" => [ "k" => "v" ]], "json", '{"k":"v"}' ],
            "missing_key"     => [ [], "no", "[]" ],
            "non_array_value" => [ [ "json" => "not an array" ], "json", "[]" ],
            "invalid_input"   => [ "not json", "any", "[]" ],
            "enum_key"        => [ [ "Key" => [ "k" => "v" ]], TestDictionaryEnum::Key, '{"k":"v"}' ],
            "integer_key"     => [ [[ "k" => "v" ]], 0, '{"k":"v"}' ],
        ];
    }


    #[DataProvider("providerDecodeAsArray")]
    public function testDecodeAsArray(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->decodeAsArray($key));
    }

    public static function providerDecodeAsArray(): array {
        return [
            "json_object"  => [ [ "jsonObj" => '{"k":"v"}' ], "jsonObj", [ "k" => "v" ] ],
            "json_array"   => [ [ "jsonArr" => '["x", 2]' ], "jsonArr", [ "x", 2 ] ],
            "invalid_json" => [ [ "bad" => "not json" ], "bad", [] ],
            "missing_key"  => [ [], "missing", [] ],
            "enum_key"     => [ [ "Key" => '{"k":"v"}' ], TestDictionaryEnum::Key, [ "k" => "v" ] ],
            "integer_key"  => [ [ '{"k":"v"}' ], 0, [ "k" => "v" ] ],
        ];
    }


    #[DataProvider("providerDecodeAsStrings")]
    public function testDecodeAsStrings(mixed $input, Enum|int|string $key, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->decodeAsStrings($key));
    }

    public static function providerDecodeAsStrings(): array {
        return [
            "json_strings"  => [ [ "jsonStr" => '["a","b"]' ], "jsonStr", [ "a", "b" ] ],
            "json_numerics" => [ [ "jsonNum" => '[1,2]' ], "jsonNum", [ "1", "2" ] ],
            "json_empty"    => [ [ "jsonEmpty" => '[]' ], "jsonEmpty", [] ],
            "invalid_json"  => [ [ "bad" => "not json" ], "bad", [] ],
            "enum_key"      => [ [ "Key" => '["a","b"]' ], TestDictionaryEnum::Key, [ "a", "b" ] ],
            "integer_key"   => [ [ '["a","b"]' ], 0, [ "a", "b" ] ],
            "missing_key"   => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerCreateMap")]
    public function testCreateMap(mixed $input, Enum|int|string $key, array $expectedKeys, array $unexpectedKeys): void {
        $d = new Dictionary($input);
        $map = $d->createMap($key);
        $this->assertInstanceOf(Dictionary::class, $map);

        foreach ($expectedKeys as $expectedKey) {
            $this->assertTrue($map->has($expectedKey));
        }

        foreach ($unexpectedKeys as $unexpectedKey) {
            $this->assertFalse($map->has($unexpectedKey));
        }
    }

    public static function providerCreateMap(): array {
        return [
            "basic_map"                => [ [[ "id" => "x", "v" => 1 ], [ "id" => "y", "v" => 2 ]], "id", [ "x", "y" ], [] ],
            "missing_key_skipped"      => [ [[ "id" => "x", "v" => 1 ], [ "v" => 2 ]], "id", [ "x" ], [ "y" ] ],
            "numeric_keys_stringified" => [ [[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]], "id", [ "1", "2" ], [] ],
            "enum_key"                 => [ [[ "Key" => "x", "v" => 1 ], [ "Key" => "y", "v" => 2 ]], TestDictionaryEnum::Key, [ "x", "y" ], [] ],
        ];
    }


    #[DataProvider("providerToArray")]
    public function testToArray(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toArray());
    }

    public static function providerToArray(): array {
        return [
            "basic_array"         => [ [ "a" => "1", "b" => 2, "c" => [ "x" ]], [ "a" => "1", "b" => 2, "c" => [ "x" ]] ],
            "nested_dictionaries" => [ [ "sub" => [ "k" => "v" ]], [ "sub" => [ "k" => "v" ]] ],
            "invalid_input"       => [ "", [] ],
        ];
    }


    #[DataProvider("providerToList")]
    public function testToList(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toList());
    }

    public static function providerToList(): array {
        return [
            "list_style_data"     => [ [ 1, "x" ], [ 1, "x" ] ],
            "associative_map"     => [ [ "a" => "1", "b" => "2" ], [ "1", "2" ] ],
            "nested_dictionaries" => [ [[ "k" => "v" ]], [[ "k" => "v" ]] ],
            "invalid_input"       => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringsMap")]
    public function testToStringsMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringsMap());
    }

    public static function providerToStringsMap(): array {
        return [
            "basic_strings_map" => [ [ "a" => 1, "b" => "", "c" => null ], [ "a" => "1", "b" => "", "c" => "" ] ],
            "numeric_keys"      => [ [ "id", 3, null ], [ "id", "3", "" ] ],
            "invalid_input"     => [ "", [] ],
        ];
    }


    #[DataProvider("providerToIntStringMap")]
    public function testToIntStringMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toIntStringMap());
    }

    public static function providerToIntStringMap(): array {
        return [
            "basic_int_string_map" => [ [ "1" => "one", "2" => "two" ], [ "1" => "one", "2" => "two" ] ],
            "invalid_input"        => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringIntMap")]
    public function testToStringIntMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringIntMap());
    }

    public static function providerToStringIntMap(): array {
        return [
            "basic_string_int_map" => [ [ "a" => 1, "b" => "2" ], [ "a" => 1, "b" => 2 ] ],
            "invalid_input"        => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStringMixedMap")]
    public function testToStringMixedMap(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStringMixedMap());
    }

    public static function providerToStringMixedMap(): array {
        return [
            "basic_string_mixed_map" => [ [ "a" => 1, "b" => "s" ], [ "a" => 1, "b" => "s" ] ],
            "nested_dictionaries"    => [ [ "sub" => [ "k" => "v" ]], [ "sub" => [ "k" => "v" ]] ],
            "invalid_input"          => [ "", [] ],
        ];
    }


    #[DataProvider("providerToStrings")]
    public function testToStrings(mixed $input, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toStrings());
    }

    public static function providerToStrings(): array {
        return [
            "basic_strings" => [ [ "a" => "1", "b" => "", "c" => 3 ], [ "1", "", "3" ] ],
            "nested_arrays" => [ [ "x" => [ 1 ], "y" => null ], [ "", "" ] ],
            "invalid_input" => [ "", [] ],
        ];
    }


    #[DataProvider("providerToInts")]
    public function testToInts(mixed $input, bool $withoutEmpty, array $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->toInts($withoutEmpty));
    }

    public static function providerToInts(): array {
        return [
            "list_of_values"        => [ [ "1", 2, "3", -4.4 ], false, [ 1, 2, 3, -4 ] ],
            "skip_empty_strings"    => [ [ "a" => "1", "b" => "", "c" => "2.5" ], true, [ 1, 3 ] ],
            "include_empty_strings" => [ [ "a" => "1", "b" => "0", "c" => "2.5" ], false, [ 1, 0, 3 ] ],
            "negative_decimal"      => [ [ "n" => "-1.6", "p" => "2.4" ], false, [ -2, 2 ] ],
            "invalid_input"         => [ "", true, [] ],
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
            "basic_json"    => [ [ "a" => "1", "b" => "" ], [ "a", "b" ], false ],
            "invalid_input" => [ "", [], true ],
        ];
    }


    #[DataProvider("providerCount")]
    public function testCount(mixed $input, int $expected): void {
        $d = new Dictionary($input);
        $this->assertEquals($expected, $d->count());
    }

    public static function providerCount(): array {
        return [
            "associative_array" => [ [ "a" => 1, "b" => 2 ], 2 ],
            "empty_dictionary"  => [ [], 0 ],
            "list_style_data"   => [ [ "x", "y", "z" ], 3 ],
            "invalid_input"     => [ "", 0 ],
        ];
    }


    #[DataProvider("providerIterator")]
    public function testIterator(mixed $input, array $expectedKeys, int $expectedCount): void {
        $d = new Dictionary($input);
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
            "basic_iteration"  => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], 2 ],
            "empty_dictionary" => [ [], [], 0 ],
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
            "basic_array"         => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], false, 1 ],
            "nested_dictionaries" => [ [ "sub" => [ "k" => "v" ]], [ "sub" ], false, null ],
            "invalid_input"       => [ "", [], true, null ],
        ];
    }
}
