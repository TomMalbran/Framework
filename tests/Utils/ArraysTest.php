<?php
namespace Tests\Utils;

use Framework\Utils\Arrays;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ArraysTest extends TestCase {

    #[DataProvider("providerIsList")]
    public function testIsList(mixed $input, bool $expected): void {
        $this->assertSame($expected, Arrays::isList($input));
    }

    public static function providerIsList(): array {
        return [
            "list"    => [ [ 1, 2, 3 ], true ],
            "assoc"   => [ [ "a" => 1 ], false ],
            "null"    => [ null, false ],
            "empty"   => [ [], true ],
            "invalid" => [ "not-an-array", false ],
        ];
    }


    #[DataProvider("providerIsArrayList")]
    public function testIsArrayList(mixed $input, bool $expected): void {
        $this->assertSame($expected, Arrays::isArrayList($input));
    }

    public static function providerIsArrayList(): array {
        return [
            "array_list" => [ [[ 1 ], [ 2 ]], true ],
            "assoc"      => [ [ "a" => [ "x" ]], false ],
            "list"       => [ [ 1, 2 ], false ],
            "null"       => [ null, false ],
            "empty"      => [ [], false ],
            "invalid"    => [ "not-an-array", false ],
        ];
    }


    #[DataProvider("providerIsDict")]
    public function testIsDict(mixed $input, bool $expected): void {
        $this->assertSame($expected, Arrays::isDict($input));
    }

    public static function providerIsDict(): array {
        return [
            "dict"    => [ [ "a" => 1 ], true ],
            "list"    => [ [ 1, 2 ], false ],
            "null"    => [ null, false ],
            "empty"   => [ [], false ],
            "invalid" => [ "not-an-array", false ],
        ];
    }


    #[DataProvider("providerIsMap")]
    public function testIsMap(mixed $input, bool $expected): void {
        $this->assertSame($expected, Arrays::isMap($input));
    }

    public static function providerIsMap(): array {
        return [
            "map"     => [ [ "a" => [ "x" => 1 ] ], true ],
            "assoc"   => [ [ "a" => 1 ], false ],
            "list"    => [ [ 1, 2 ], false ],
            "null"    => [ null, false ],
            "empty"   => [ [], false ],
            "invalid" => [ "not-an-array", false ],
        ];
    }


    #[DataProvider("providerToArray")]
    public function testToArray(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toArray($input));
    }

    public static function providerToArray(): array {
        return [
            "array"  => [ [ 1, 2 ], [ 1, 2 ] ],
            "string" => [ "x", [ "x" ] ],
            "null"   => [ null, [] ],
            "object" => [ (object)[ "a" => 1 ], [ "a" => 1 ] ],
            "asoc"   => [ [ "a" => 1 ], [ "a" => 1 ] ],
        ];
    }


    #[DataProvider("providerToList")]
    public function testToList(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toList($input));
    }

    public static function providerToList(): array {
        return [
            "array"  => [ [ 1, 2 ], [ 1, 2 ] ],
            "number" => [ 1, [ 1 ] ],
            "string" => [ "x", [ "x" ] ],
            "null"   => [ null, [] ],
            "object" => [ (object)[ "a" => 1, "b" => 2 ], [ 1, 2 ] ],
            "asoc"   => [ [ "a" => 1, "b" => 2 ], [ 1, 2 ] ],
        ];
    }


    #[DataProvider("providerToObject")]
    public function testToObject(mixed $input, mixed $expected): void {
        $this->assertEquals($expected, Arrays::toObject($input));
    }

    public static function providerToObject(): array {
        return [
            "array" => [ [ "a" => 1 ], [ "a" => 1 ] ],
            "null"  => [ null, (object)[] ],
            "empty" => [ [], (object)[] ],
        ];
    }


    #[DataProvider("providerToInts")]
    public function testToInts(mixed $input, string $key, bool $withoutEmpty, array $expected): void {
        $this->assertSame($expected, Arrays::toInts($input, $key, $withoutEmpty));
    }

    public static function providerToInts(): array {
        return [
            "zero_no_empty"      => [ 0, "", true, [] ],
            "single_int"         => [ 5, "", false, [ 5 ] ],
            "non_array_int"      => [ "not-array", "", false, [] ],
            "num_str_ints"       => [ [ "1", 2 ], "", false, [ 1, 2 ] ],
            "mixed_skip_non_num" => [ [ "1", "x" ], "", false, [ 1 ] ],
            "key_extract"        => [ [[ "v" => "3" ], [ "v" => "4" ], [ "v" => "" ], [ "v" => 0 ]], "v", false, [ 3, 4, 0 ] ],
            "key_no_empty"       => [ [[ "v" => "3" ], [ "v" => "4" ], [ "v" => "" ], [ "v" => 0 ]], "v", true, [ 3, 4 ] ],
            "objs_key_extract"   => [ [(object)[ "v" => "5" ], (object)[ "v" => "6" ]], "v", false, [ 5, 6 ] ],
        ];
    }


    #[DataProvider("providerToStrings")]
    public function testToStrings(mixed $input, string $key, bool $withoutEmpty, array $expected): void {
        $this->assertSame($expected, Arrays::toStrings($input, $key, $withoutEmpty));
    }

    public static function providerToStrings(): array {
        return [
            "simple_list"           => [ [ "a", "b" ], "", false, [ "a", "b" ] ],
            "non_array_non_string"  => [ 123, "", false, [] ],
            "scalar_string"         => [ "s", "", false, [ "s" ] ],
            "empty_scalar"          => [ "", "", true, [] ],
            "numeric_values"        => [ [ 0 ], "", false, [ "0" ] ],
            "without_empty"         => [ [ 0 ], "", true, [] ],
            "key_extraction"        => [ [[ "v" => "x" ], [ "v" => "" ], [ "v" => null ], [ "v" => 5 ]], "v", false, [ "x", "", "", "5" ] ],
            "key_without_empty"     => [ [[ "v" => "x" ], [ "v" => "" ], [ "v" => null ], [ "v" => 5 ]], "v", true, [ "x", "5" ] ],
            "objects_as_rows"       => [ [(object)[ "v" => "a" ], (object)[ "v" => 0 ]], "v", false, [ "a", "0" ] ],
            "objects_without_empty" => [ [(object)[ "v" => "a" ], (object)[ "v" => 0 ]], "v", true, [ "a" ] ],
            "single_object"         => [ (object)[ "v" => "a" ], "", false, [] ],
        ];
    }

    #[DataProvider("providerToIntsMap")]
    public function testToIntsMap(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toIntsMap($input));
    }

    public static function providerToIntsMap(): array {
        return [
            "simple_conversion"   => [ [ "1" => 1 ], [ 1 => 1 ] ],
            "numeric_keys"        => [ [ 1 => "1" ], [ 1 => 1 ] ],
            "string_numeric_keys" => [ [ "1.23" => "x" ], [ 1 => 0 ] ],
            "non_array_string"    => [ "x", [] ],
            "null_input"          => [ null, [] ],
            "null_values"         => [ [ "1" => null ], [ 1 => 0 ] ],
            "object_values"       => [ [ "2" => (object)[] ], [ 2 => 0 ] ],
        ];
    }

    #[DataProvider("providerToStringsMap")]
    public function testToStringsMap(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toStringsMap($input));
    }

    public static function providerToStringsMap(): array {
        return [
            "simple_conversion"   => [ [ "a" => 1 ], [ "a" => "1" ] ],
            "numeric_keys"        => [ [ 1 => "v" ], [ "1" => "v" ] ],
            "string_numeric_keys" => [ [ "1.23" => "x" ], [ "1.23" => "x" ] ],
            "non_array_string"    => [ "x", [] ],
            "null_input"          => [ null, [] ],
            "null_values"         => [ [ "a" => null ], [ "a" => "" ] ],
            "object_values"       => [ [ "k" => (object)[] ], [ "k" => "" ] ],
        ];
    }


    #[DataProvider("providerToIntStringMap")]
    public function testToIntStringMap(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toIntStringMap($input));
    }

    public static function providerToIntStringMap(): array {
        return [
            "numeric_string_keys" => [ [ "1" => "v" ], [ 1 => "v" ] ],
            "integer_keys"        => [ [ 2 => "x" ], [ 2 => "x" ] ],
            "float_like_keys"     => [ [ "1.23" => "x" ], [ 1 => "x" ] ],
            "non_numeric_keys"    => [ [ "a" => "a" ], [ 0 => "a" ] ],
            "key_collisions"      => [ [ "1" => "a", "1.0" => "b" ], [ 1 => "b" ] ],
            "values_to_strings"   => [ [ "1" => 2 ], [ 1 => "2" ] ],
            "null_to_empty"       => [ [ "1" => null ], [ 1 => "" ] ],
            "object_to_empty"     => [ [ "1" => (object)[] ], [ 1 => "" ] ],
            "non_array_input"     => [ "x", [] ],
        ];
    }


    #[DataProvider("providerToStringIntMap")]
    public function testToStringIntMap(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toStringIntMap($input));
    }

    public static function providerToStringIntMap(): array {
        return [
            "string_numeric"    => [ [ "k" => "1" ], [ "k" => 1 ] ],
            "integer"           => [ [ "k" => 2 ], [ "k" => 2 ] ],
            "float_like_string" => [ [ "k" => "1.23" ], [ "k" => 1 ] ],
            "null_value"        => [ [ "k" => null ], [ "k" => 0 ] ],
            "non_numeric"       => [ [ "k" => "x" ], [ "k" => 0 ] ],
            "object_value"      => [ [ "k" => (object)[] ], [ "k" => 0 ] ],
            "non_array_input"   => [ "x", [] ],
        ];
    }


    #[DataProvider("providerToStringFloatMap")]
    public function testToStringFloatMap(mixed $input, int $decimals, array $expected): void {
        $this->assertSame($expected, Arrays::toStringFloatMap($input, $decimals));
    }

    public static function providerToStringFloatMap(): array {
        return [
            "string_numeric_with_decimals" => [ [ "k" => "1.23" ], 2, [ "k" => 1.23 ] ],
            "integer_with_decimals"        => [ [ "k" => 123 ], 2, [ "k" => 1.23 ] ],
            "float_preserved"              => [ [ "k" => 1.23 ], 2, [ "k" => 1.23 ] ],
            "numeric_string_no_decimals"   => [ [ "k" => "123" ], 2, [ "k" => 123.0 ] ],
            "null_to_zero"                 => [ [ "k" => null ], 2, [ "k" => 0.0 ] ],
            "non_array_input"              => [ "x", 2, [] ],
        ];
    }


    #[DataProvider("providerToStringMixedMap")]
    public function testToStringMixedMap(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::toStringMixedMap($input));
    }

    public static function providerToStringMixedMap(): array {
        $obj = (object)[ "x" => 1 ];
        return [
            "nested_array_preserved"   => [ [ "k" => [ "x" => 1 ] ], [ "k" => [ "x" => 1 ] ] ],
            "scalar_value_preserved"   => [ [ "k" => 1 ], [ "k" => 1 ] ],
            "string_value_preserved"   => [ [ "k" => "v" ], [ "k" => "v" ] ],
            "object_value_preserved"   => [ [ "k" => $obj ], [ "k" => $obj ] ],
            "non_array_input"          => [ "x", [] ],
        ];
    }


    #[DataProvider("providerGetValues")]
    public function testGetValues(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::getValues($input));
    }

    public static function providerGetValues(): array {
        $obj = (object)[ "x" => 1 ];
        return [
            "assoc"          => [ [ "a" => 1, "b" => 2 ], [ 1, 2 ] ],
            "list"           => [ [ 1, 2, 3 ], [ 1, 2, 3 ] ],
            "non_sequential" => [ [ 0 => 10, 2 => 30 ], [ 10, 30 ] ],
            "empty"          => [ [], [] ],
            "object_values"  => [ [ "a" => $obj ], [ $obj ] ],
        ];
    }


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(mixed $input, ?string $key, bool $expected): void {
        $this->assertSame($expected, Arrays::isEmpty($input, $key));
    }

    public static function providerIsEmpty(): array {
        return [
            "empty_array"           => [ [], null, true ],
            "array_with_value"      => [ [ 1 ], null, false ],
            "empty_string_by_key"   => [ [ "a" => "" ], "a", true ],
            "null_input"            => [ null, null, true ],
            "non_empty_scalar"      => [ "x", null, false ],
            "empty_string"          => [ "", null, true ],
            "zero"                  => [ 0, null, true ],
            "false"                 => [ false, null, true ],
            "zero_by_key"           => [ [ "a" => 0 ], "a", true ],
            "false_by_key"          => [ [ "a" => false ], "a", true ],
            "missing_key"           => [ [], "missing", true ],
            "array_with_empty_vals" => [ [ "a" => "" ], null, false ],
        ];
    }


    #[DataProvider("providerLength")]
    public function testLength(mixed $input, int $expected): void {
        $this->assertSame($expected, Arrays::length($input));
    }

    public static function providerLength(): array {
        return [
            "list"   => [ [ 1, 2, 3 ], 3 ],
            "assoc"  => [ [ "a" => 1, "b" => 2 ], 2 ],
            "empty"  => [ [], 0 ],
            "null"   => [ null, 0 ],
            "nested" => [ [[ 1 ], [ 2 ]], 2 ],
        ];
    }


    #[DataProvider("providerContains")]
    public function testContains(mixed $input, mixed $needle, ?string $key, bool $caseInsensitive, bool $atLeastOne, bool $expected): void {
        $this->assertSame($expected, Arrays::contains($input, $needle, $key, $caseInsensitive, $atLeastOne));
    }

    public static function providerContains(): array {
        $rowsObj       = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        $rowsObjSingle = [(object)[ "id" => 1 ]];

        return [
            "simple_values"             => [ [[ "id" => 1 ], [ "id" => 2 ]], 2, "id", true, false, true ],
            "not_found"                 => [ [ 1, 2, 3 ], 4, null, true, false, false ],
            "array_exact_match"         => [ [ "a", "b" ], [ "a", "b" ], null, true, false, true ],
            "single_scalar_value"       => [ "x", "x", null, true, false, true ],
            "objects_by_property"       => [ $rowsObj, 2, "id", true, false, true ],
            "array_needle_at_least_one" => [ [[ "id" => 1 ], [ "id" => 2 ]], [ 2, 3 ], "id", true, true, true ],
            "array_needle_all_required" => [ [[ "id" => 1 ]], [ 1, 2 ], "id", true, false, false ],
            "case_insensitive"          => [ [ "A" ], "a", null, true, false, true ],
            "case_sensitive"            => [ [ "A" ], "a", null, false, false, false ],
            "multiple_values_all_match" => [ [[ "id" => 1 ], [ "id" => 2 ], [ "id" => 3 ]], [ 2, 3 ], "id", true, false, true ],
            "at_least_one_no_match"     => [ [[ "id" => 1 ]], [ 2, 3 ], "id", true, true, false ],
            "invalid_key"               => [ [[ "id" => 1 ]], 1, "nonexistent_key", true, false, false ],
            "invalid_key_objects"       => [ $rowsObjSingle, 1, "nonexistent_key", true, false, false ],
            "null_values_in_array"      => [ [ null ], 1, "id", true, false, false ],
        ];
    }


    #[DataProvider("providerContainsKey")]
    public function testContainsKey(mixed $input, mixed $key, bool $expected): void {
        $this->assertSame($expected, Arrays::containsKey($input, $key));
    }

    public static function providerContainsKey(): array {
        return [
            "simple_key"            => [ [ "a" => 1 ], "a", true ],
            "missing_key"           => [ [ "a" => 1 ], "b", false ],
            "numeric_string_to_int" => [ [ "1" => "v" ], 1, true ],
            "int_to_string"         => [ [ 1 => "v" ], "1", false ],
            "numeric_int_key"       => [ [ 1 => "v" ], 1, true ],
            "empty_array"           => [ [], "x", false ],
            "float_like_string_key" => [ [ "1.23" => "x" ], "1.23", true ],
        ];
    }


    #[DataProvider("providerIsEqual")]
    public function testIsEqual(mixed $a, mixed $b, string $key, bool $expected): void {
        $this->assertSame($expected, Arrays::isEqual($a, $b, $key));
    }

    public static function providerIsEqual(): array {
        return [
            "simple_equal"            => [ [ 1, 2 ], [ 1, 2 ], "", true ],
            "different_length"        => [ [ 1 ], [ 1, 2 ], "", false ],
            "different_order"         => [ [ 1, 2 ], [ 2, 1 ], "", false ],
            "type_mismatch"           => [ [ 1 ], [ "1" ], "", false ],
            "different_assoc_keys"    => [ [ "x" => 1 ], [ "y" => 1 ], "", false ],
            "nested_by_key_reordered" => [ [[ "id" => 1 ], [ "id" => 2 ]], [[ "id" => 2 ], [ "id" => 1 ]], "id", true ],
            "nested_missing_key"      => [ [[ "id" => 1 ], [ "no" => 2 ]], [[ "id" => 1 ], [ "id" => 2 ]], "id", false ],
        ];
    }


    #[DataProvider("providerIsEqualWithKeys")]
    public function testIsEqualWithKeys(mixed $a, mixed $b, array $keys, bool $expected): void {
        $this->assertSame($expected, Arrays::isEqualWithKeys($a, $b, $keys));
    }

    public static function providerIsEqualWithKeys(): array {
        return [
            "equal_arrays"       => [ [ "x" => 1, "y" => 2 ], [ "x" => 1, "y" => 2 ], [ "x", "y" ], true ],
            "unequal_values"     => [ [ "x" => 1, "y" => 2 ], [ "x" => 1, "y" => 3 ], [ "x", "y" ], false ],
            "different_order"    => [ [ "x" => 1, "y" => 2 ], [ "y" => 2, "x" => 1 ], [ "x", "y" ], true ],
            "missing_keys"       => [ [ "x" => 1, "y" => 2 ], [ "x" => 1 ], [ "x", "y" ], false ],
            "extra_keys_ignored" => [ [ "x" => 1, "y" => 2 ], [ "x" => 1, "y" => 2, "z" => 3 ], [ "x", "y" ], true ],
            "type_difference"    => [ [ "x" => 1, "y" => 2 ], [ "x" => 1, "y" => "2" ], [ "x", "y" ], false ],
        ];
    }


    #[DataProvider("providerIsEqualJSON")]
    public function testIsEqualJSON(mixed $a, mixed $b, bool $expected): void {
        $this->assertSame($expected, Arrays::isEqualJSON($a, $b));
    }

    public static function providerIsEqualJSON(): array {
        return [
            "array_vs_object"         => [ [ "a" => 1 ], (object)[ "a" => 1 ], true ],
            "array_vs_json_string"    => [ [ "a" => 1 ], '{"a":1}', true ],
            "list_order_matters"      => [ [ 1, 2 ], [ 2, 1 ], false ],
            "different_keys"          => [ [ "a" => 1 ], [ "b" => 1 ], false ],
            "null_values"             => [ null, null, true ],
            "nested_object_vs_string" => [ (object)[ "x" => (object)[ "y" => 2 ] ], '{"x":{"y":2}}', true ],
            "invalid_json_string"     => [ [ "a" => 1 ], '{invalid}', false ],
        ];
    }


    #[DataProvider("providerIntersects")]
    public function testIntersects(mixed $a, mixed $b, bool $expected): void {
        $this->assertSame($expected, Arrays::intersects($a, $b));
    }

    public static function providerIntersects(): array {
        $obj = (object)[ "x" => 1 ];
        return [
            "simple_overlap"            => [ [ 1, 2 ], [ 2, 3 ], true ],
            "no_overlap"                => [ [ 1 ], [ 2 ], false ],
            "empty_arrays"              => [ [], [], false ],
            "empty_with_values"         => [ [], [ 1 ], false ],
            "identical_arrays"          => [ [ 1, 2 ], [ 1, 2 ], true ],
            "strict_type_comparison"    => [ [ 1 ], [ "1" ], false ],
            "nested_arrays_by_value"    => [ [[ "a" => 1 ]], [[ "a" => 1 ]], true ],
            "same_object_instance"      => [ [ $obj ], [ $obj ], true ],
            "different_object_instance" => [ [ $obj ], [ (object)[ "x" => 1 ] ], false ],
        ];
    }


    #[DataProvider("providerGetDiff")]
    public function testGetDiff(mixed $a, mixed $b, string $key, ?string $returnKey, array $expected): void {
        $this->assertSame($expected, Arrays::getDiff($a, $b, $key, $returnKey));
    }

    public static function providerGetDiff(): array {
        return [
            "basic_diff"          => [ [[ "id" => 1 ], [ "id" => 2 ]], [[ "id" => 2 ]], "id", null, [[ "id" => 1 ]] ],
            "extract_id_key"      => [ [[ "id" => 1 ], [ "id" => 2 ]], [[ "id" => 2 ]], "id", "id", [ 1 ] ],
            "identical_arrays"    => [ [[ "id" => 1 ], [ "id" => 2 ]], [[ "id" => 1 ], [ "id" => 2 ]], "id", null, [] ],
            "empty_other"         => [ [[ "id" => 1 ], [ "id" => 2 ]], [], "id", null, [[ "id" => 1 ], [ "id" => 2 ]] ],
            "empty_other_extract" => [ [[ "id" => 1 ], [ "id" => 2 ]], [], "id", "id", [ 1, 2 ] ],
            "mixed_non_arrays"    => [ [ [ "id" => 1 ], 5, "x" ], [], "id", null, [[ "id" => 1 ]] ],
            "missing_check_key"   => [ [[ "id" => 1 ], [ "no" => 3 ]], [[ "id" => 1 ]], "id", null, [[ "no" => 3 ]] ],
        ];
    }


    #[DataProvider("providerAddFirst")]
    public function testAddFirst(mixed $input, array $elements, array $expected): void {
        $this->assertSame($expected, Arrays::addFirst($input, ...$elements));
    }

    public static function providerAddFirst(): array {
        $o = (object)[ "x" => 1 ];
        return [
            "single_element"    => [ [ 1, 2 ], [ 0 ], [ 0, 1, 2 ] ],
            "multiple_elements" => [ [ 3 ], [ 1, 2 ], [ 1, 2, 3 ] ],
            "add_to_empty"      => [ [], [ "a" ], [ "a" ] ],
            "object_preserved"  => [ [], [ $o ], [ $o ] ],
        ];
    }


    #[DataProvider("providerAddAt")]
    public function testAddAt(mixed $input, int $index, array $elements, array $expected): void {
        $this->assertSame($expected, Arrays::addAt($input, $index, ...$elements));
    }

    public static function providerAddAt(): array {
        $o = (object)[ "x" => 1 ];
        return [
            "insert_middle"         => [ [ 1, 2 ], 1, [ 99 ], [ 1, 99, 2 ] ],
            "insert_start"          => [ [ 1, 2 ], 0, [ 0 ], [ 0, 1, 2 ] ],
            "insert_multiple"       => [ [ 1, 4 ], 1, [ 2, 3 ], [ 1, 2, 3, 4 ] ],
            "insert_negative_index" => [ [ 1, 2, 3 ], -1, [ 9 ], [ 1, 2, 9, 3 ] ],
            "insert_beyond_end"     => [ [ 1 ], 10, [ 2 ], [ 1, 2 ] ],
            "object_preserved"      => [ [], 0, [ $o ], [ $o ] ],
        ];
    }


    #[DataProvider("providerRemoveFirst")]
    public function testRemoveFirst(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::removeFirst($input));
    }

    public static function providerRemoveFirst(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "simple_removal"    => [ [ 1, 2, 3 ], [ 2, 3 ] ],
            "single_element"    => [ [ 1 ], [] ],
            "empty_array"       => [ [], [] ],
            "objects_preserved" => [ [ $o1, $o2 ], [ $o2 ] ],
        ];
    }


    #[DataProvider("providerRemoveLast")]
    public function testRemoveLast(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::removeLast($input));
    }

    public static function providerRemoveLast(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "simple_list"       => [ [ 1, 2, 3 ], [ 1, 2 ] ],
            "single_element"    => [ [ 1 ], [] ],
            "empty_array"       => [ [], [] ],
            "objects_preserved" => [ [ $o1, $o2 ], [ $o1 ] ],
            "assoc_array"       => [ [ "a" => 1, "b" => 2 ], [ "a" => 1 ] ],
        ];
    }


    #[DataProvider("providerRemoveAt")]
    public function testRemoveAt(mixed $input, int $index, array $expected): void {
        $this->assertSame($expected, Arrays::removeAt($input, $index));
    }

    public static function providerRemoveAt(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "remove_middle"       => [ [ 1, 2, 3 ], 1, [ 1, 3 ] ],
            "remove_at_start"     => [ [ 1, 2, 3 ], 0, [ 2, 3 ] ],
            "remove_at_end"       => [ [ 1, 2, 3 ], -1, [ 1, 2 ] ],
            "single_element"      => [ [ 1 ], 0, [] ],
            "position_beyond_end" => [ [ 1 ], 10, [ 1 ] ],
            "objects_preserved"   => [ [ $o1, $o2 ], 1, [ $o1 ] ],
        ];
    }


    #[DataProvider("providerRemoveValue")]
    public function testRemoveValue(mixed $input, mixed $value, string $key, array $expected): void {
        $this->assertSame($expected, Arrays::removeValue($input, $value, $key));
    }

    public static function providerRemoveValue(): array {
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        return [
            "remove_by_key_from_rows"    => [ [[ "id" => 1 ], [ "id" => 2 ]], 1, "id", [[ "id" => 2 ]] ],
            "remove_primitive_from_list" => [ [ 1, 2, 3 ], 1, "", [ 2, 3 ] ],
            "non_existing_value"         => [ [ 1, 2 ], 3, "", [ 1, 2 ] ],
            "remove_all_occurrences"     => [ [ 1, 2, 1 ], 1, "", [ 2 ] ],
            "objects_preserved"          => [ [ $o1, $o2 ], 1, "id", [ $o2 ] ],
            "rows_missing_key"           => [ [[ "id" => 1 ], [ "no" => 3 ]], 1, "id", [] ],
            "strict_type_comparison"     => [ [ "1", "2" ], 1, "", [ "1", "2" ] ],
        ];
    }


    #[DataProvider("providerRemoveEmpty")]
    public function testRemoveEmpty(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::removeEmpty($input));
    }

    public static function providerRemoveEmpty(): array {
        $o1 = (object)[];
        $o2 = (object)[ "x" => 1 ];
        return [
            "basic_removal"         => [ [ 1, "", null, "a" ], [ 1, "a" ] ],
            "empty_input"           => [ [], [] ],
            "removes_falsy_values"  => [ [ 0, 1, "", null, false, "a", "0" ], [ 1, "a" ] ],
            "nested_arrays_objects" => [ [ [], [ 1 ], $o1, $o2 ], [ [ 1 ], $o1, $o2 ] ],
        ];
    }


    #[DataProvider("providerRemoveDuplicates")]
    public function testRemoveDuplicates(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::removeDuplicates($input));
    }

    public static function providerRemoveDuplicates(): array {
        return [
            "basic_numeric"     => [ [ 1, 2, 1, 2 ], [ 1, 2 ] ],
            "string_duplicates" => [ [ "a", "b", "a" ], [ "a", "b" ] ],
            "strict_types"      => [ [ 1, "1", 1 ], [ 1 ] ],
            "empty_input"       => [ [], [] ],
        ];
    }


    #[DataProvider("providerMerge")]
    public function testMerge(mixed $a, mixed $b, array $expected): void {
        $this->assertSame($expected, Arrays::merge($a, $b));
    }

    public static function providerMerge(): array {
        $o = (object)[ "k" => "v" ];
        return [
            "simple_associative_merge" => [ [ "a" => 1 ], [ "b" => 2 ], [ "a" => 1, "b" => 2 ] ],
            "key_conflict_later_wins"  => [ [ "a" => 1 ], [ "a" => 2 ], [ "a" => 2 ] ],
            "numeric_keys_reindexed"   => [ [ 1, 2 ], [ 3 ], [ 1, 2, 3 ] ],
            "nested_arrays_replaced"   => [ [ "x" => [ "y" => 1 ] ], [ "x" => [ "z" => 2 ] ], [ "x" => [ "z" => 2 ] ] ],
            "object_preserved"         => [ [ "k" => $o ], [], [ "k" => $o ] ],
            "merge_with_empty"         => [ [], [ "a" => 1 ], [ "a" => 1 ] ],
        ];
    }


    #[DataProvider("providerMergeLists")]
    public function testMergeLists(mixed $a, mixed $b, array $expected): void {
        $this->assertSame($expected, Arrays::mergeLists($a, $b));
    }

    public static function providerMergeLists(): array {
        $o = (object)[ "x" => 1 ];
        return [
            "simple_concatenation"      => [ [ 1, 2 ], [ 3 ], [ 1, 2, 3 ] ],
            "merge_with_empty"          => [ [], [ 1 ], [ 1 ] ],
            "duplicates_preserved"      => [ [ 1, 2 ], [ 2, 3 ], [ 1, 2, 2, 3 ] ],
            "variadic_multiple_lists"   => [ [ 1 ], [ 2 ], [ 1, 2 ] ],
            "object_elements_preserved" => [ [], [ $o ], [ $o ] ],
        ];
    }


    #[DataProvider("providerSlice")]
    public function testSlice(mixed $input, int $from, ?int $amount, array $expected): void {
        $this->assertSame($expected, Arrays::slice($input, $from, $amount));
    }

    public static function providerSlice(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "basic_slice_with_amount"  => [ [ 1, 2, 3 ], 1, 2, [ 2, 3 ] ],
            "slice_to_end_omit_amount" => [ [ 1, 2, 3 ], 2, null, [ 3 ] ],
            "amount_larger_remaining"  => [ [ 1, 2, 3 ], 1, 10, [ 2, 3 ] ],
            "negative_from_index"      => [ [ 1, 2, 3 ], -2, 1, [ 2 ] ],
            "empty_input"              => [ [], 0, 2, [] ],
            "objects_preserved"        => [ [ $o1, $o2 ], 1, 1, [ $o2 ] ],
        ];
    }


    #[DataProvider("providerPaginate")]
    public function testPaginate(mixed $input, int $page, int $amount, array $expected): void {
        $this->assertSame($expected, Arrays::paginate($input, $page, $amount));
    }

    public static function providerPaginate(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "basic_pagination"          => [ [ 1, 2, 3 ], 0, 2, [ 1, 2 ] ],
            "second_page"               => [ [ 1, 2, 3, 4 ], 1, 2, [ 3, 4 ] ],
            "page_beyond_available"     => [ [ 1, 2, 3 ], 2, 2, [] ],
            "amount_larger_remaining"   => [ [ 1, 2, 3 ], 1, 5, [] ],
            "empty_input"               => [ [], 0, 3, [] ],
            "object_identity_preserved" => [ [ $o1, $o2 ], 1, 1, [ $o2 ] ],
        ];
    }


    #[DataProvider("providerSubArray")]
    public function testSubArray(mixed $input, mixed $selector, array $expected): void {
        $this->assertSame($expected, Arrays::subArray($input, $selector));
    }

    public static function providerSubArray(): array {
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        return [
            "basic_intersection"        => [ [ 1, 2, 3 ], [ 2, 4 ], [ 2 ] ],
            "no_matches"                => [ [ 1, 2, 3 ], [ 4, 5 ], [] ],
            "no_duplicates"             => [ [ 1, 2, 2, 3 ], [ 2 ], [ 2 ] ],
            "empty_source"              => [ [], [ 1, 2 ], [] ],
            "empty_selector"            => [ [ 1, 2 ], [], [] ],
            "object_identity_preserved" => [ [ $o1, $o2 ], [ $o2 ], [ $o2 ] ],
        ];
    }


    #[DataProvider("providerExtend")]
    public function testExtend(mixed $a, mixed $b, array $expected): void {
        $this->assertSame($expected, Arrays::extend($a, $b));
    }

    public static function providerExtend(): array {
        $obj = (object)[ "x" => 1 ];
        return [
            "nested_merge"              => [ [ "x" => [ "y" => 1 ] ], [ "x" => [ "z" => 2 ] ], [ "x" => [ "y" => 1, "z" => 2 ] ] ],
            "adds_new_top_level_keys"   => [ [ "a" => 1 ], [ "b" => 2 ], [ "a" => 1, "b" => 2 ] ],
            "scalar_keys_overridden"    => [ [ "k" => 1 ], [ "k" => 2 ], [ "k" => 2 ] ],
            "extend_empty_array"        => [ [ "a" => 1 ], [], [ "a" => 1 ] ],
            "object_identity_preserved" => [ [ "o" => $obj ], [], [ "o" => $obj ] ],
        ];
    }


    #[DataProvider("providerSort")]
    public function testSort(mixed $input, ?callable $comparator, array $expected): void {
        $this->assertSame($expected, Arrays::sort($input, $comparator));
    }

    public static function providerSort(): array {
        $o1 = (object)[ "v" => 2 ];
        $o2 = (object)[ "v" => 1 ];
        return [
            "simple_ascending"      => [ [ 3, 1, 2 ], null, [ 1, 2, 3 ] ],
            "descending_comparator" => [ [ 1, 2, 3 ], fn($a, $b) => $b <=> $a, [ 3, 2, 1 ] ],
            "assoc_preserve_keys"   => [ [ "c" => 3, "a" => 1, "b" => 2 ], fn($x, $y) => $x <=> $y, [ "a" => 1, "b" => 2, "c" => 3 ] ],
            "objects_by_property"   => [ [ $o1, $o2 ], fn($x, $y) => $x->v <=> $y->v, [ $o2, $o1 ] ],
        ];
    }


    #[DataProvider("providerSortList")]
    public function testSortList(mixed $input, ?callable $comparator, array $expected): void {
        $this->assertSame($expected, Arrays::sortList($input, $comparator));
    }

    public static function providerSortList(): array {
        $o1 = (object)[ "v" => 2 ];
        $o2 = (object)[ "v" => 1 ];
        return [
            "simple_sort"          => [ [ 2, 1 ], null, [ 1, 2 ] ],
            "empty_list"           => [ [], null, [] ],
            "duplicates_preserved" => [ [ 2, 3, 1, 2 ], null, [ 1, 2, 2, 3 ] ],
            "custom_descending"    => [ [ 1, 2, 3 ], fn($a, $b) => $b <=> $a, [ 3, 2, 1 ] ],
            "objects_by_property"  => [ [ $o1, $o2 ], fn($x, $y) => $x->v <=> $y->v, [ $o2, $o1 ] ],
        ];
    }


    #[DataProvider("providerReverse")]
    public function testReverse(mixed $input, array $expected): void {
        $this->assertSame($expected, Arrays::reverse($input));
    }

    public static function providerReverse(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "simple_reverse"    => [ [ 1, 2, 3 ], [ 3, 2, 1 ] ],
            "empty_list"        => [ [], [] ],
            "single_element"    => [ [ 1 ], [ 1 ] ],
            "objects_preserved" => [ [ $o1, $o2 ], [ $o2, $o1 ] ],
        ];
    }


    #[DataProvider("providerMap")]
    public function testMap(mixed $input, callable $callback, array $expected): void {
        $this->assertSame($expected, Arrays::map($input, $callback));
    }

    public static function providerMap(): array {
        $o1 = (object)[ "x" => 1 ];
        $o2 = (object)[ "x" => 2 ];
        return [
            "simple_mapping"             => [ [ 1, 2 ], fn($v) => $v * 2, [ 2, 4 ] ],
            "empty_input"                => [ [], fn($v) => $v * 2, [] ],
            "assoc_preserve_keys"        => [ [ "a" => 1, "b" => 2 ], fn($v) => $v * 2, [ "a" => 2, "b" => 4 ] ],
            "different_return_type"      => [ [ 1, 2 ], fn($v) => (string)$v, [ "1", "2" ] ],
            "object_instances_preserved" => [ [ $o1, $o2 ], fn($v) => $v, [ $o1, $o2 ] ],
        ];
    }


    #[DataProvider("providerRandom")]
    public function testRandom(mixed $input, array $expected): void {
        $this->assertContains(Arrays::random($input), $expected);
    }

    public static function providerRandom(): array {
        $o1 = (object)[ "a" => 1 ];
        $o2 = (object)[ "b" => 2 ];
        return [
            "simple_list"          => [ [ 10, 20, 30 ], [ 10, 20, 30 ] ],
            "assoc_array"          => [ [ "a" => 100, "b" => 200 ], [ 100, 200 ] ],
            "single_element"       => [ [ 42 ], [ 42 ] ],
            "empty_array"          => [ [], [ null ] ],
            "non_consecutive_keys" => [ [ 5 => "x", 999 => "y" ], [ "x", "y" ] ],
            "objects_preserved"    => [ [ $o1, $o2 ], [ $o1, $o2 ] ],
        ];
    }


    #[DataProvider("providerMax")]
    public function testMax(mixed $input, int|float $expected): void {
        $this->assertSame($expected, Arrays::max($input));
    }

    public static function providerMax(): array {
        return [
            "simple_values"    => [ [ 1, 5, 3 ], 5 ],
            "empty_array"      => [ [], 0 ],
            "negative_numbers" => [ [ -3, -1, -2 ], -1 ],
            "assoc_array"      => [ [ "a" => 7, "b" => 2 ], 7 ],
            "single_element"   => [ [ 4 ], 4 ],
            "null_input"       => [ null, 0 ],
        ];
    }


    #[DataProvider("providerSum")]
    public function testSum(mixed $input, ?string $key, int|float $expected): void {
        $this->assertSame($expected, Arrays::sum($input, $key));
    }

    public static function providerSum(): array {
        return [
            "simple_sum"           => [ [ 1, 2, 3 ], null, 6 ],
            "sum_by_key"           => [ [[ "v" => 1 ], [ "v" => 2 ]], "v", 3 ],
            "empty_input"          => [ [], null, 0 ],
            "negative_values"      => [ [ -1, 2 ], null, 1 ],
            "floats_preserved"     => [ [ 1.5, 2.25 ], null, 3.75 ],
            "numeric_strings"      => [ [ "1", "2" ], null, 3.0 ],
            "missing_keys_ignored" => [ [[ "v" => 1 ], [ "x" => 2 ]], "v", 1 ],
        ];
    }


    #[DataProvider("providerAverage")]
    public function testAverage(mixed $input, int $decimals, ?string $key, float $expected): void {
        $this->assertSame($expected, Arrays::average($input, $decimals, $key));
    }

    public static function providerAverage(): array {
        return [
            "simple_average"        => [ [ 1, 2, 3 ], 0, null, 2.0 ],
            "single_element"        => [ [ 4 ], 0, null, 4.0 ],
            "negative_numbers"      => [ [ -1, 1 ], 0, null, 0.0 ],
            "empty_input"           => [ [], 0, null, 0.0 ],
            "numeric_strings"       => [ [ "1", "2", "3" ], 0, null, 2.0 ],
            "with_decimals"         => [ [ 1.2, 1.8 ], 1, null, 1.5 ],
            "assoc_arrays"          => [ [ "a" => 1, "b" => 3 ], 0, null, 2.0 ],
            "by_key_missing_values" => [ [[ "v" => 1 ], [ "x" => 2 ]], 1, "v", 0.5 ],
            "by_key_all_present"    => [ [[ "v" => 1 ], [ "v" => 2 ]], 1, "v", 1.5 ],
        ];
    }


    #[DataProvider("providerCreateMap")]
    public function testCreateMap(mixed $input, string $key, array $expected): void {
        $this->assertSame($expected, Arrays::createMap($input, $key));
    }

    public static function providerCreateMap(): array {
        $o1 = (object)[ "id" => 1, "v" => "a" ];
        $o2 = (object)[ "id" => 2, "v" => "b" ];
        return [
            "basic_map"           => [ [[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]], "id", [ 1 => [ "id" => 1, "v" => "a" ], 2 => [ "id" => 2, "v" => "b" ] ] ],
            "duplicates_override" => [ [[ "id" => 1, "v" => "a" ], [ "id" => 1, "v" => "c" ]], "id", [ 1 => [ "id" => 1, "v" => "c" ] ] ],
            "missing_key_ignored" => [ [[ "no" => 1 ], [ "id" => 2, "v" => "b" ]], "id", [ 2 => [ "id" => 2, "v" => "b" ] ] ],
            "objects_preserved"   => [ [ $o1, $o2 ], "id", [ 1 => $o1, 2 => $o2 ] ],
        ];
    }


    #[DataProvider("providerCreateArray")]
    public function testCreateArray(mixed $input, mixed $key, bool $distinct, array $expected): void {
        $this->assertSame($expected, Arrays::createArray($input, $key, false, $distinct));
    }

    public static function providerCreateArray(): array {
        $o1 = (object)[ "id" => 1 ];
        $o2 = (object)[ "id" => 2 ];
        return [
            "simple_extraction"       => [ [[ "id" => 1, "v" => "a" ], [ "id" => 2, "v" => "b" ]], "id", false, [ 1, 2 ] ],
            "duplicates_preserved"    => [ [[ "id" => 1 ], [ "id" => 1 ]], "id", false, [ 1, 1 ] ],
            "distinct_removes_dupes"  => [ [[ "id" => 1 ], [ "id" => 1 ]], "id", true, [ 1 ] ],
            "multiple_key_extraction" => [ [[ "a" => 1, "b" => 2 ], [ "a" => 3, "b" => 4 ]], [ "a", "b" ], false, [ "1 - 2", "3 - 4" ] ],
            "objects_preserved"       => [ [ $o1, $o2 ], "id", false, [ 1, 2 ] ],
            "empty_input"             => [ [], "id", false, [] ],
            "null_key_returns_rows"   => [ [[ "id" => 5 ], [ "id" => 6 ]], null, false, [[ "id" => 5 ], [ "id" => 6 ]] ],
        ];
    }


    #[DataProvider("providerGetFirst")]
    public function testGetFirst(mixed $input, string $key, mixed $expected): void {
        $this->assertSame($expected, Arrays::getFirst($input, $key));
    }

    public static function providerGetFirst(): array {
        $obj = (object)[ "v" => "z" ];
        return [
            "simple_list"      => [ [ 1, 2, 3 ], "", 1 ],
            "extract_by_key"   => [ [ [ "x" => "a" ] ], "x", "a" ],
            "empty_input"      => [ [], "", null ],
            "assoc_array"      => [ [ "a" => 1, "b" => 2 ], "", 1 ],
            "object_preserved" => [ [ $obj ], "", $obj ],
            "missing_key"      => [ [[ "a" => 1 ]], "missing", "" ],
        ];
    }


    #[DataProvider("providerGetFirstKey")]
    public function testGetFirstKey(mixed $input, mixed $expected): void {
        $this->assertSame($expected, Arrays::getFirstKey($input));
    }

    public static function providerGetFirstKey(): array {
        return [
            "assoc_array"          => [ [ "a" => 1, "b" => 2 ], "a" ],
            "empty_array"          => [ [], null ],
            "numeric_list"         => [ [ 10, 20 ], 0 ],
            "non_consecutive_keys" => [ [ 5 => "x", 10 => "y" ], 5 ],
            "numeric_string_key"   => [ [ "1" => "v" ], 1 ],
        ];
    }


    #[DataProvider("providerGetLast")]
    public function testGetLast(mixed $input, string $key, mixed $expected): void {
        $this->assertSame($expected, Arrays::getLast($input, $key));
    }

    public static function providerGetLast(): array {
        return [
            "simple_list"          => [ [ 1, 2, 3 ], "", 3 ],
            "assoc_array"          => [ [ "a" => 1, "b" => 2 ], "", 2 ],
            "empty_array"          => [ [], "", null ],
            "non_consecutive_keys" => [ [ 5 => "x", 10 => "y" ], "", "y" ],
            "rows_with_key"        => [ [[ "x" => "a" ], [ "x" => "b" ]], "x", "b" ],
            "missing_key_on_last"  => [ [[ "a" => 1 ], [ "b" => 2 ]], "missing", "" ],
        ];
    }


    #[DataProvider("providerGetIndex")]
    public function testGetIndex(mixed $input, mixed $needle, bool $caseInsensitive, int $expected): void {
        $this->assertSame($expected, Arrays::getIndex($input, $needle, $caseInsensitive));
    }

    public static function providerGetIndex(): array {
        return [
            "simple_value_found"      => [ [ 1, 2, 3 ], 2, false, 1 ],
            "not_found"               => [ [ 1 ], 5, false, -1 ],
            "duplicates_first_index"  => [ [ 1, 2, 2 ], 2, false, 1 ],
            "empty_array"             => [ [], "x", false, -1 ],
            "case_sensitive_no_match" => [ [ "A" ], "a", false, -1 ],
            "case_insensitive_match"  => [ [ "A" ], "a", true, 0 ],
        ];
    }


    #[DataProvider("providerFindIndex")]
    public function testFindIndex(mixed $input, string $key, mixed $value, mixed $expected): void {
        $this->assertSame($expected, Arrays::findIndex($input, $key, $value));
    }

    public static function providerFindIndex(): array {
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        $assoc   = [ "a" => [ "id" => 1 ], "b" => [ "id" => 2 ] ];
        return [
            "simple_found_at_first"         => [ [[ "id" => 1 ]], "id", 1, 0 ],
            "not_found_empty_input"         => [ [], "id", 1, -1 ],
            "found_at_second_position"      => [ [[ "id" => 9 ], [ "id" => 10 ]], "id", 10, 1 ],
            "works_with_object_rows"        => [ $rowsObj, "id", 2, 1 ],
            "missing_key_returns_minus_one" => [ [[ "no" => 1 ]], "id", 1, -1 ],
            "strict_comparison_no_match"    => [ [[ "id" => "1" ]], "id", 1, -1 ],
            "assoc_array_returns_key"       => [ $assoc, "id", 2, "b" ],
        ];
    }


    #[DataProvider("providerHasValue")]
    public function testHasValue(mixed $input, string $key, mixed $value, bool $expected): void {
        $this->assertSame($expected, Arrays::hasValue($input, $key, $value));
    }

    public static function providerHasValue(): array {
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ]];
        return [
            "simple_present"         => [ [[ "id" => 1 ]], "id", 1, true ],
            "empty_input"            => [ [], "id", 1, false ],
            "works_with_object_rows" => [ $rowsObj, "id", 2, true ],
            "missing_key"            => [ [[ "no" => 1 ]], "id", 1, false ],
            "strict_comparison"      => [ [[ "id" => "1" ]], "id", 1, false ],
            "duplicates_handled"     => [ [[ "id" => 2 ], [ "id" => 2 ]], "id", 2, true ],
        ];
    }


    #[DataProvider("providerFindValue")]
    public function testFindValue(mixed $input, string $key, mixed $value, mixed $expected): void {
        $this->assertSame($expected, Arrays::findValue($input, $key, $value));
    }

    public static function providerFindValue(): array {
        $rowsObj = [(object)[ "id" => 1, "x" => 9 ], (object)[ "id" => 2, "x" => 8 ]];
        $assoc   = [ "a" => [ "id" => 1, "x" => 9 ], "b" => [ "id" => 2, "x" => 8 ] ];
        return [
            "simple_found_at_first"  => [ [[ "id" => 1, "x" => 9 ], [ "id" => 2, "x" => 8 ]], "id", 1, [ "id" => 1, "x" => 9 ] ],
            "works_with_object_rows" => [ $rowsObj, "id", 2, $rowsObj[1] ],
            "empty_input"            => [ [], "id", 1, null ],
            "missing_key"            => [ [[ "no" => 1 ]], "id", 1, null ],
            "strict_comparison"      => [ [[ "id" => "1" ]], "id", 1, null ],
            "multiple_matches"       => [ [[ "id" => 2, "x" => 1 ], [ "id" => 2, "x" => 2 ]], "id", 2, [ "id" => 2, "x" => 1 ] ],
            "assoc_array"            => [ $assoc, "id", 2, $assoc["b"] ],
        ];
    }


    #[DataProvider("providerFindValues")]
    public function testFindValues(mixed $input, string $key, mixed $value, array $expected): void {
        $this->assertSame($expected, Arrays::findValues($input, $key, $value));
    }

    public static function providerFindValues(): array {
        $rowsObj = [(object)[ "id" => 1 ], (object)[ "id" => 2 ], (object)[ "id" => 1 ]];
        $assoc   = [ "a" => [ "id" => 1 ], "b" => [ "id" => 1 ] ];
        return [
            "simple_multiple_matches"    => [ [[ "id" => 1 ], [ "id" => 1 ], [ "id" => 2 ]], "id", 1, [[ "id" => 1 ], [ "id" => 1 ]] ],
            "objects_preserved"          => [ $rowsObj, "id", 1, [ $rowsObj[0], $rowsObj[2] ] ],
            "empty_input"                => [ [], "id", 1, [] ],
            "rows_missing_key"           => [ [[ "no" => 1 ], [ "id" => 1 ]], "id", 1, [[ "id" => 1 ]] ],
            "strict_comparison_no_match" => [ [[ "id" => "1" ]], "id", 1, [] ],
            "assoc_array"                => [ $assoc, "id", 1, [ $assoc["a"], $assoc["b"] ] ],
        ];
    }


    #[DataProvider("providerGetKey")]
    public function testGetKey(string $key, string $prefix, string $expected): void {
        $this->assertSame($expected, Arrays::getKey($key, $prefix));
    }

    public static function providerGetKey(): array {
        return [
            "no_prefix"                => [ "name", "", "name" ],
            "with_prefix"              => [ "name", "Pref", "PrefName" ],
            "preserve_uppercase_first" => [ "Name", "Pre", "PreName" ],
            "lowercase_prefix"         => [ "x", "pre", "preX" ],
            "empty_prefix"             => [ "k", "", "k" ],
        ];
    }


    #[DataProvider("providerGetValue")]
    public function testGetValue(mixed $input, mixed $key, string $glue, string $prefix, bool $useEmpty, mixed $default, mixed $expected): void {
        $this->assertSame($expected, Arrays::getValue($input, $key, $glue, $prefix, $useEmpty, $default));
    }

    public static function providerGetValue(): array {
        $obj     = (object)[ "k" => "v" ];
        $objMult = (object)[ "name" => "n", "age" => 10 ];
        return [
            "empty_key_ret_arr"       => [ [ "a" => [ "b" => "c" ]], "", " - ", "", false, null, [ "a" => [ "b" => "c" ] ] ],
            "empty_key_ret_scal"      => [ "scalar", "", " - ", "", false, null, "scalar" ],
            "single_key_arr"          => [ [ "k" => "v" ], "k", " - ", "", false, null, "v" ],
            "single_key_obj"          => [ $obj, "k", " - ", "", false, null, "v" ],
            "miss_key_ret_def"        => [ [ "a" => 1 ], "x", " - ", "", false, "D", "D" ],
            "empty_val_miss"          => [ [ "k" => "" ], "k", " - ", "", false, "X", "X" ],
            "empty_val_use_true"      => [ [ "k" => "" ], "k", " - ", "", true, "X", "" ],
            "num_zero_key"            => [ [ 0 => "zero" ], "0", " - ", "", false, null, [ 0 => "zero" ] ],
            "multi_key_def_glue"      => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], " - ", "", false, null, "1 - 2" ],
            "multi_key_cust_glue"     => [ [ "a" => 1, "b" => 2 ], [ "a", "b" ], ", ", "", false, null, "1, 2" ],
            "pfx_single_key"          => [ [ "preName" => "pv" ], "name", " - ", "pre", false, null, "pv" ],
            "pfx_multi_key_cust_glue" => [ [ "preA" => 1, "preB" => 2 ], [ "a", "b" ], " + ", "pre", false, null, "1 + 2" ],
            "multi_key_none_exist"    => [ [ "a" => 1 ], [ "x", "y" ], " - ", "", false, "D", "" ],
            "obj_multi_key"           => [ $objMult, [ "name", "age" ], " - ", "", false, null, "n - 10" ],
        ];
    }


    #[DataProvider("providerGetOneValue")]
    public function testGetOneValue(mixed $input, mixed $key, bool $useEmpty, mixed $default, mixed $expected): void {
        $this->assertSame($expected, Arrays::getOneValue($input, $key, $useEmpty, $default));
    }

    public static function providerGetOneValue(): array {
        $obj = (object)[ "k" => "v" ];
        return [
            "simple_array_key"            => [ [ "k" => "v" ], "k", false, null, "v" ],
            "object_property"             => [ $obj, "k", false, null, "v" ],
            "missing_key_returns_null"    => [ [], "x", false, null, null ],
            "empty_string_treated_empty"  => [ [ "k" => "" ], "k", false, null, null ],
            "empty_string_use_empty"      => [ [ "k" => "" ], "k", true, null, "" ],
            "zero_value_treated_empty"    => [ [ "k" => 0 ], "k", false, null, null ],
            "zero_value_use_empty"        => [ [ "k" => 0 ], "k", true, null, 0 ],
            "missing_key_returns_default" => [ [], "x", false, "D", "D" ],
            "numeric_string_key"          => [ [ 0 => "zero" ], "0", false, null, "zero" ],
        ];
    }
}
