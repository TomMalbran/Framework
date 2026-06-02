<?php
namespace Tests\IO;

use Framework\IO\Request;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;
use Framework\File\File;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RequestTest extends TestCase {
    use TestHelpers;

    protected function setUp(): void {
        $_FILES = [
            "f" => [
                "name"     => "a.txt",
                "type"     => "text/plain",
                "tmp_name" => $this->tmpFileF,
                "error"    => 0,
                "size"     => file_exists($this->tmpFileF) ? filesize($this->tmpFileF) : 0,
            ],
            "g" => [
                "name"     => "image.PNG",
                "type"     => "image/png",
                "tmp_name" => $this->tmpFileG,
                "error"    => 0,
                "size"     => file_exists($this->tmpFileG) ? filesize($this->tmpFileG) : 0,
            ],
            "h" => [
                "name"     => "big.bin",
                "type"     => "application/octet-stream",
                "tmp_name" => $this->tmpFileH,
                "error"    => UPLOAD_ERR_INI_SIZE,
                "size"     => 0,
            ]
        ];
    }

    protected function tearDown(): void {
        $_REQUEST = [];
        $_FILES = [];
    }

    private function assertDateResult(mixed $result, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        if ($shouldBeEmpty) {
            $this->assertTrue($result->isEmpty());
        } else {
            $this->assertSame($expected, $result->toString($format));
        }
    }


    #[DataProvider("providerConstruct")]
    public function testConstruct(array $requestData, array $cases): void {
        $_REQUEST = $requestData;
        $request = new Request(withRequest: true);
        foreach ($cases as [ $args, $expected ]) {
            $this->assertSame($expected, $request->get(...$args));
        }
    }

    public static function providerConstruct(): array {
        return [
            "with_request"  => [ [ "a" => 1, "b" => "x" ], [ [[ "a" ], 1], [[ "b" ], "x" ] ]],
            "empty_request" => [ [], [ [[ "missing" ], "" ]]],
        ];
    }


    #[DataProvider("providerGet")]
    public function testGet(array $input, string $key, mixed $expected, mixed $default = null): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->get($key, $default));
    }

    public static function providerGet(): array {
        $input = [ "a" => 1, "b" => "x", "c" => "" ];
        return [
            "int"         => [ $input, "a", 1 ],
            "string"      => [ $input, "b", "x" ],
            "empty"       => [ $input, "c", "" ],
            "default"     => [ $input, "missing", "def", "def" ],
            "missing"     => [ $input, "missing", null, null ],
            "empty_input" => [ [], "missing", "def", "def" ],
        ];
    }


    #[DataProvider("providerGetOr")]
    public function testGetOr(array $input, string $key, mixed $default, mixed $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getOr($key, $default));
    }

    public static function providerGetOr(): array {
        $input = [ "a" => 1, "b" => "x", "c" => "" ];
        return [
            "int"         => [ $input, "a", "def", 1 ],
            "string"      => [ $input, "b", "def", "x" ],
            "empty"       => [ $input, "c", "def", "def" ],
            "default"     => [ $input, "missing", "def", "def" ],
            "null_value"  => [ $input, "missing", null, null ],
            "empty_input" => [ [], "missing", "def", "def" ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(array $input, string $key, int $expected, int $default = 0): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getInt($key, $default));
    }

    public static function providerGetInt(): array {
        $input = [ "a" => 10, "b" => "10", "c" => "x", "d" => "" ];
        return [
            "int"         => [ $input, "a", 10 ],
            "numeric"     => [ $input, "b", 10 ],
            "invalid"     => [ $input, "c", 0 ],
            "empty"       => [ $input, "d", 0 ],
            "missing"     => [ $input, "missing", 0 ],
            "default"     => [ $input, "missing", 5, 5 ],
            "empty_input" => [ [], "missing", 5, 5 ],
        ];
    }


    #[DataProvider("providerGetFloat")]
    public function testGetFloat(array $input, string $key, float $expected, float $default = 0.0): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getFloat($key, $default));
    }

    public static function providerGetFloat(): array {
        $input = [ "a" => "1.5", "b" => 2.5, "c" => "2", "d" => "x" ];
        return [
            "string"      => [ $input, "a", 1.5 ],
            "float"       => [ $input, "b", 2.5 ],
            "int_like"    => [ $input, "c", 2.0 ],
            "invalid"     => [ $input, "d", 0.0 ],
            "missing"     => [ $input, "missing", 0.0 ],
            "default"     => [ $input, "missing", 3.5, 3.5 ],
            "empty_input" => [ [], "missing", 3.5, 3.5 ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(array $input, string $key, string $expected, string $default = ""): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getString($key, $default));
    }

    public static function providerGetString(): array {
        $input = [ "a" => "  hello  ", "b" => 123, "c" => "" ];
        return [
            "trimmed"     => [ $input, "a", "hello" ],
            "scalar"      => [ $input, "b", "123" ],
            "empty"       => [ $input, "c", "" ],
            "missing"     => [ $input, "missing", "" ],
            "default"     => [ $input, "missing", "default", "default" ],
            "empty_input" => [ [], "missing", "default", "default" ],
        ];
    }


    #[DataProvider("providerGetArray")]
    public function testGetArray(array $input, string $key, array $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getArray($key));
    }

    public static function providerGetArray(): array {
        $input = [
            "a" => [ "", "a", null, 1 ],
            "b" => "x",
            "c" => [],
        ];
        return [
            "array"       => [ $input, "a", [ "a", 1 ] ],
            "scalar"      => [ $input, "b", [] ],
            "empty"       => [ $input, "c", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetDictionary")]
    public function testGetDictionary(array $input, string $key, array $expected): void {
        $result = (new Request($input))->getDictionary($key);
        $this->assertInstanceOf(Dictionary::class, $result);
        $this->assertSame($expected, $result->toArray());
    }

    public static function providerGetDictionary(): array {
        $input = [
            "a" => JSON::encode([ "x" => 1 ]),
            "b" => "x",
        ];
        return [
            "json"        => [ $input, "a", [ "x" => 1 ] ],
            "invalid"     => [ $input, "b", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetJSONArray")]
    public function testGetJSONArray(array $input, string $key, array $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getJSONArray($key));
    }

    public static function providerGetJSONArray(): array {
        $input = [
            "a" => JSON::encode([ 1, 2, 3 ]),
            "b" => "x",
            "c" => JSON::encode([]),
        ];
        return [
            "json"        => [ $input, "a", [ 1, 2, 3 ] ],
            "invalid"     => [ $input, "b", [] ],
            "empty"       => [ $input, "c", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetInts")]
    public function testGetInts(array $input, string $key, array $expected, bool $withoutEmpty = true): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getInts($key, $withoutEmpty));
    }

    public static function providerGetInts(): array {
        $input = [
            "a" => JSON::encode([ "4", "5" ]),
            "b" => "1,2,3",
            "c" => [ "6", "x", 7 ],
            "d" => [ 1, 0, "2", "0" ],
            "e" => "x",
            "f" => "",
        ];
        return [
            "json"        => [ $input, "a", [ 4, 5 ] ],
            "csv"         => [ $input, "b", [ 1, 2, 3 ] ],
            "mixed"       => [ $input, "c", [ 6, 7 ] ],
            "keep_zeroes" => [ $input, "d", [ 1, 0, 2, 0 ], false ],
            "invalid"     => [ $input, "e", [] ],
            "empty"       => [ $input, "f", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetStrings")]
    public function testGetStrings(array $input, string $key, array $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getStrings($key));
    }

    public static function providerGetStrings(): array {
        $input = [
            "a" => JSON::encode([ "x", "y" ]),
            "b" => "a,b,c",
            "c" => [ "x", null, "y", "" ],
            "d" => "x",
            "e" => "",
        ];
        return [
            "json"        => [ $input, "a", [ "x", "y" ] ],
            "csv"         => [ $input, "b", [ "a", "b", "c" ] ],
            "array"       => [ $input, "c", [ "x", "y" ] ],
            "scalar"      => [ $input, "d", [ "x" ] ],
            "empty"       => [ $input, "e", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetStringsMap")]
    public function testGetStringsMap(array $input, string $key, array $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getStringsMap($key));
    }

    public static function providerGetStringsMap(): array {
        $input = [
            "a" => JSON::encode([ "a" => "v" ]),
            "b" => [ "a" => 1, "b" => "x" ],
            "c" => "x",
            "d" => JSON::encode([]),
        ];
        return [
            "json"        => [ $input, "a", [ "a" => "v" ] ],
            "array"       => [ $input, "b", [ "a" => "1", "b" => "x" ] ],
            "invalid"     => [ $input, "c", [] ],
            "empty"       => [ $input, "d", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerGetStringIntMap")]
    public function testGetStringIntMap(array $input, string $key, array $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getStringIntMap($key));
    }

    public static function providerGetStringIntMap(): array {
        $input = [
            "a" => JSON::encode([ "a" => 2 ]),
            "b" => [ "a" => "2", "b" => "x" ],
            "c" => "x",
            "d" => JSON::encode([]),
        ];
        return [
            "json"        => [ $input, "a", [ "a" => 2 ] ],
            "array"       => [ $input, "b", [ "a" => 2, "b" => 0 ] ],
            "invalid"     => [ $input, "c", [] ],
            "empty"       => [ $input, "d", [] ],
            "missing"     => [ $input, "missing", [] ],
            "empty_input" => [ [], "missing", [] ],
        ];
    }


    #[DataProvider("providerSet")]
    public function testSet(array $operations, string $method, array $args, mixed $expected): void {
        $request = new Request();
        foreach ($operations as [ $key, $value ]) {
            $request->set($key, $value);
        }
        $this->assertSame($expected, $request->{$method}(...$args));
    }

    public static function providerSet(): array {
        $operations = [
            [ "x", 1 ],
            [ "x", "2" ],
            [ "arr", [ "", "b", null, 3 ] ],
            [ "n", null ],
        ];
        return [
            "get_value"   => [ $operations, "get", [ "x" ], "2" ],
            "get_null"    => [ $operations, "get", [ "n" ], "" ],
            "get_array"   => [ $operations, "getArray", [ "arr" ], [ "b", 3 ] ],
            "exists_null" => [ $operations, "exists", [ "n" ], false ],
            "has_null"    => [ $operations, "has", [ "n" ], false ],
        ];
    }


    #[DataProvider("providerRemove")]
    public function testRemove(array $input, array $removeKeys, string $method, array $args, mixed $expected): void {
        $request = new Request($input);
        foreach ($removeKeys as $key) {
            $request->remove($key);
        }
        $this->assertSame($expected, $request->{$method}(...$args), "Method: {$method}, args: " . var_export($args, true));
    }

    public static function providerRemove(): array {
        $input = [ "a" => 1, "b" => "x" ];
        $removeKeys = [ "a", "missing" ];
        return [
            "removed_key" => [ $input, $removeKeys, "exists", [ "a" ], false ],
            "missing_key" => [ $input, $removeKeys, "exists", [ "missing" ], false ],
            "default_get" => [ $input, $removeKeys, "get", [ "a", "def" ], "def" ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->has(...$args));
    }

    public static function providerHas(): array {
        $filledRequest = [
            "a" => "",
            "b" => "1",
            "c" => [ "z" ],
            "d" => null,
            "e" => 0,
        ];
        return [
            "no_args"        => [ $filledRequest, [], true ],
            "empty_string"   => [ $filledRequest, [ "a" ], false ],
            "string"         => [ $filledRequest, [ "b" ], true ],
            "array"          => [ $filledRequest, [ "c" ], true ],
            "null"           => [ $filledRequest, [ "d" ], false ],
            "missing"        => [ $filledRequest, [ "missing" ], false ],
            "all_present"    => [ $filledRequest, [ [ "b", "c" ] ], true ],
            "one_empty"      => [ $filledRequest, [ [ "a", "b" ] ], false ],
            "array_index"    => [ $filledRequest, [ "c", 0 ], true ],
            "missing_index"  => [ $filledRequest, [ "c", 1 ], false ],
            "null_key"       => [ $filledRequest, [ null ], true ],
            "empty_null_key" => [ [], [ null ], false ],
        ];
    }


    #[DataProvider("providerExists")]
    public function testExists(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->exists(...$args));
    }

    public static function providerExists(): array {
        $input = [ "a" => "", "b" => "1", "c" => null, "d" => 0 ];
        return [
            "empty_string"  => [ $input, [ "a" ], true ],
            "null"          => [ $input, [ "c" ], false ],
            "zero"          => [ $input, [ "d" ], true ],
            "missing"       => [ $input, [ "missing" ], false ],
            "all_present"   => [ $input, [ [ "a", "b", "d" ] ], true ],
            "contains_null" => [ $input, [ [ "a", "c" ] ], false ],
            "empty_input"   => [ [], [ "a" ], false ],
        ];
    }


    #[DataProvider("providerIsActive")]
    public function testIsActive(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isActive(...$args));
    }

    public static function providerIsActive(): array {
        $input = [
            "a" => "1",
            "b" => true,
            "c" => "true",
            "d" => 1,
            "e" => "",
            "f" => null,
            "g" => 0,
        ];
        return [
            "one"      => [ $input, [ "a" ], true ],
            "true"     => [ $input, [ "b" ], true ],
            "true_str" => [ $input, [ "c" ], true ],
            "int_one"  => [ $input, [ "d" ], true ],
            "empty"    => [ $input, [ "e" ], false ],
            "null"     => [ $input, [ "f" ], false ],
            "zero"     => [ $input, [ "g" ], false ],
            "missing"  => [ $input, [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isEmpty(...$args));
    }

    public static function providerIsEmpty(): array {
        $input = [
            "a" => "1",
            "b" => "2",
            "c" => "",
            "d" => [],
            "e" => null,
        ];
        return [
            "filled_keys"    => [ $input, [ [ "a" ], [ "b" ] ], false ],
            "missing_key"    => [ $input, [ [ "missing" ] ], true ],
            "empty_string"   => [ $input, [ [ "c" ] ], true ],
            "partly_missing" => [ $input, [ [ "a" ], [ "missing" ] ], true ],
            "empty_array"    => [ $input, [ [ "d" ] ], true ],
            "null"           => [ $input, [ [ "e" ] ], true ],
            "empty_input"    => [ [], [ [ "a" ] ], true ],
        ];
    }


    #[DataProvider("providerIsEmptyArray")]
    public function testIsEmptyArray(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isEmptyArray(...$args));
    }

    public static function providerIsEmptyArray(): array {
        $input = [ "a" => [], "b" => [ 1 ], "c" => "x" ];
        return [
            "empty_array"  => [ $input, [ "a" ], true ],
            "filled_array" => [ $input, [ "b" ], false ],
            "scalar"       => [ $input, [ "c" ], true ],
            "missing"      => [ $input, [ "missing" ], true ],
            "empty_input"  => [ [], [ "missing" ], true ],
        ];
    }


    #[DataProvider("providerToBinary")]
    public function testToBinary(array $input, array $args, int $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->toBinary(...$args));
    }

    public static function providerToBinary(): array {
        $input = [
            "a" => "1",
            "b" => true,
            "c" => false,
            "d" => "true",
            "e" => "false",
            "f" => "0",
            "g" => "",
        ];
        return [
            "string_one"  => [ $input, [ "a", 1 ], 1 ],
            "true_bool"   => [ $input, [ "b", 1 ], 1 ],
            "false_bool"  => [ $input, [ "c", 1 ], 0 ],
            "true_str"    => [ $input, [ "d", 1 ], 1 ],
            "false_str"   => [ $input, [ "e", 1 ], 0 ],
            "zero_str"    => [ $input, [ "f", 1 ], 0 ],
            "empty"       => [ $input, [ "g", 1 ], 0 ],
            "missing"     => [ $input, [ "missing", 1 ], 0 ],
            "empty_input" => [ [], [ "missing", 1 ], 0 ],
        ];
    }


    #[DataProvider("providerToInt")]
    public function testToInt(array $input, array $args, int $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->toInt(...$args));
    }

    public static function providerToInt(): array {
        $input = [
            "a" => "12.34",
            "b" => "12.345",
            "c" => "12.3",
            "d" => "abc",
            "e" => "",
        ];
        return [
            "two_decimals"   => [ $input, [ "a", 2 ], 1234 ],
            "three_decimals" => [ $input, [ "b", 3 ], 12345 ],
            "pad_decimal"    => [ $input, [ "c", 2 ], 1230 ],
            "invalid"        => [ $input, [ "d", 2 ], 0 ],
            "empty"          => [ $input, [ "e", 2 ], 0 ],
            "missing"        => [ $input, [ "missing", 2 ], 0 ],
            "empty_input"    => [ [], [ "missing", 2 ], 0 ],
        ];
    }


    #[DataProvider("providerToCents")]
    public function testToCents(array $input, array $args, int $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->toCents(...$args));
    }

    public static function providerToCents(): array {
        $input = [
            "a" => "12.34",
            "b" => "-1.23",
            "c" => "abc",
            "d" => "",
        ];
        return [
            "decimal"     => [ $input, [ "a" ], 1234 ],
            "negative"    => [ $input, [ "b" ], -123 ],
            "invalid"     => [ $input, [ "c" ], 0 ],
            "empty"       => [ $input, [ "d" ], 0 ],
            "missing"     => [ $input, [ "missing" ], 0 ],
            "empty_input" => [ [], [ "missing" ], 0 ],
        ];
    }


    #[DataProvider("providerToJSON")]
    public function testToJSON(array $input, array $args, string $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->toJSON(...$args));
    }

    public static function providerToJSON(): array {
        $input = [
            "a" => [ 1, 2 ],
            "b" => "1,2,3",
            "c" => JSON::encode([ 4, 5 ]),
            "d" => "",
            "e" => [],
        ];
        return [
            "array"       => [ $input, [ "a" ], JSON::encode([ 1, 2 ]) ],
            "csv"         => [ $input, [ "b" ], JSON::encode([ "1", "2", "3" ]) ],
            "json"        => [ $input, [ "c" ], JSON::encode([ 4, 5 ]) ],
            "empty"       => [ $input, [ "d" ], JSON::encode([]) ],
            "empty_array" => [ $input, [ "e" ], JSON::encode([]) ],
            "missing"     => [ $input, [ "missing" ], JSON::encode([]) ],
            "empty_input" => [ [], [ "missing" ], JSON::encode([]) ],
        ];
    }


    #[DataProvider("providerCuitToNumber")]
    public function testCuitToNumber(array $input, array $args, string $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->cuitToNumber(...$args));
    }

    public static function providerCuitToNumber(): array {
        $input = [
            "a" => "20 12345678 6",
            "b" => "20123456786",
            "c" => "20-12345678-6",
            "d" => "invalid-cuit",
            "e" => "",
        ];
        return [
            "spaced"      => [ $input, [ "a" ], "20123456786" ],
            "plain"       => [ $input, [ "b" ], "20123456786" ],
            "formatted"   => [ $input, [ "c" ], "20123456786" ],
            "invalid"     => [ $input, [ "d" ], "" ],
            "empty"       => [ $input, [ "e" ], "" ],
            "missing"     => [ $input, [ "missing" ], "" ],
            "empty_input" => [ [], [ "missing" ], "" ],
        ];
    }


    #[DataProvider("providerDniToNumber")]
    public function testDniToNumber(array $input, array $args, string $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->dniToNumber(...$args));
    }

    public static function providerDniToNumber(): array {
        $input = [
            "a" => "12.345.678",
            "b" => "12345678",
            "c" => "12 345 678",
            "d" => "invalid-dni",
            "e" => "",
        ];
        return [
            "formatted"   => [ $input, [ "a" ], "12345678" ],
            "plain"       => [ $input, [ "b" ], "12345678" ],
            "spaced"      => [ $input, [ "c" ], "12345678" ],
            "invalid"     => [ $input, [ "d" ], "" ],
            "empty"       => [ $input, [ "e" ], "" ],
            "missing"     => [ $input, [ "missing" ], "" ],
            "empty_input" => [ [], [ "missing" ], "" ],
        ];
    }


    #[DataProvider("providerPhoneToNumber")]
    public function testPhoneToNumber(array $input, array $args, string $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->phoneToNumber(...$args));
    }

    public static function providerPhoneToNumber(): array {
        $input = [
            "a" => "(123)456-7890",
            "b" => "123-456-7890",
            "c" => "+1 123 456 7890",
            "d" => "1234567",
            "e" => "invalid-phone",
            "f" => "",
        ];
        return [
            "paren"       => [ $input, [ "a" ], "1234567890" ],
            "dashes"      => [ $input, [ "b" ], "1234567890" ],
            "international" => [ $input, [ "c" ], "11234567890" ],
            "short"       => [ $input, [ "d" ], "1234567" ],
            "invalid"     => [ $input, [ "e" ], "" ],
            "empty"       => [ $input, [ "f" ], "" ],
            "missing"     => [ $input, [ "missing" ], "" ],
            "empty_input" => [ [], [ "missing" ], "" ],
        ];
    }


    #[DataProvider("providerToDomain")]
    public function testToDomain(array $input, array $args, string $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->toDomain(...$args));
    }

    public static function providerToDomain(): array {
        $input = [
            "a" => "https://sub.example.com/path",
            "b" => "http://www.example.com",
            "c" => "https://example.com/path",
            "d" => "not-a-url",
            "e" => "",
        ];
        return [
            "subdomain"   => [ $input, [ "a" ], "sub.example.com" ],
            "strip_www"   => [ $input, [ "b" ], "example.com" ],
            "root"        => [ $input, [ "c" ], "example.com" ],
            "invalid"     => [ $input, [ "d" ], "not-a-url" ],
            "empty"       => [ $input, [ "e" ], "" ],
            "missing"     => [ $input, [ "missing" ], "" ],
            "empty_input" => [ [], [ "missing" ], "" ],
        ];
    }


    #[DataProvider("providerToDate")]
    public function testToDate(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDate(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDate(): array {
        $input = [
            "a" => "2020-02-01",
            "b" => "01-02-2020",
            "c" => "invalid-date",
            "d" => "",
        ];
        return [
            "iso"         => [ $input, [ "a" ], DateFormat::Reverse, "2020-02-01", false ],
            "dmy"         => [ $input, [ "b" ], DateFormat::Reverse, "2020-02-01", false ],
            "invalid"     => [ $input, [ "c" ], DateFormat::Reverse, null, true ],
            "empty"       => [ $input, [ "d" ], DateFormat::Reverse, null, true ],
            "missing"     => [ $input, [ "missing" ], DateFormat::Reverse, null, true ],
            "empty_input" => [ [], [ "missing" ], DateFormat::Reverse, null, true ],
        ];
    }


    #[DataProvider("providerToTimeHour")]
    public function testToTimeHour(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toTimeHour(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToTimeHour(): array {
        $input = [
            "a" => "2020-02-01",
            "b" => "01-02-2020",
            "c" => "10:00",
            "d" => "23:59",
            "e" => "00:00",
            "f" => "invalid-time",
        ];
        return [
            "iso_time"     => [ $input, [ "a", "c" ], DateFormat::ReverseTime, "2020-02-01 10:00", false ],
            "dmy_time"     => [ $input, [ "b", "d" ], DateFormat::ReverseTime, "2020-02-01 23:59", false ],
            "midnight"     => [ $input, [ "a", "e" ], DateFormat::ReverseTime, "2020-02-01 00:00", false ],
            "invalid_time" => [ $input, [ "f", "c" ], DateFormat::ReverseTime, null, true ],
            "missing_hour" => [ $input, [ "a", "missing", true, true ], DateFormat::ReverseTime, null, true ],
            "missing_date" => [ $input, [ "missing", "c", true, true ], DateFormat::ReverseTime, null, true ],
            "empty_input"  => [ [], [ "missing", "c", true, true ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayMoment")]
    public function testToDayMoment(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayMoment(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayMoment(): array {
        $input = [
            "a" => "2020-01-01",
            "b" => "01-01-2020",
            "c" => "2020-02-29",
            "d" => "not-a-date",
            "e" => "",
        ];
        return [
            "start"       => [ $input, [ "a", DateType::Start ], DateFormat::ReverseTime, "2020-01-01 00:00", false ],
            "middle"      => [ $input, [ "a", DateType::Middle ], DateFormat::ReverseTime, "2020-01-01 12:00", false ],
            "end"         => [ $input, [ "a", DateType::End ], DateFormat::ReverseTime, "2020-01-01 23:59", false ],
            "dmy"         => [ $input, [ "b", DateType::Start ], DateFormat::ReverseTime, "2020-01-01 00:00", false ],
            "leap"        => [ $input, [ "c", DateType::Start ], DateFormat::ReverseTime, "2020-02-29 00:00", false ],
            "invalid"     => [ $input, [ "d", DateType::Start ], DateFormat::ReverseTime, null, true ],
            "empty"       => [ $input, [ "e", DateType::Start ], DateFormat::ReverseTime, null, true ],
            "missing"     => [ $input, [ "missing", DateType::Start ], DateFormat::ReverseTime, null, true ],
            "empty_input" => [ [], [ "missing", DateType::Start ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayStart")]
    public function testToDayStart(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayStart(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayStart(): array {
        $input = [
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ];
        return [
            "iso"         => [ $input, [ "a" ], DateFormat::ReverseTime, "2020-03-15 00:00", false ],
            "dmy"         => [ $input, [ "b" ], DateFormat::ReverseTime, "2020-03-15 00:00", false ],
            "empty"       => [ $input, [ "c" ], DateFormat::ReverseTime, null, true ],
            "invalid"     => [ $input, [ "d" ], DateFormat::ReverseTime, null, true ],
            "missing"     => [ $input, [ "missing" ], DateFormat::ReverseTime, null, true ],
            "empty_input" => [ [], [ "missing" ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayStartHour")]
    public function testToDayStartHour(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayStartHour(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayStartHour(): array {
        $input = [
            "a"   => "2020-03-15",
            "a_h" => "09:30",
            "b"   => "15-03-2020",
            "c"   => "",
            "d"   => "not-a-date",
            "e"   => "2020-03-16",
            "e_h" => "invalid-time",
        ];
        return [
            "date_and_hour" => [ $input, [ "a", "a_h" ], DateFormat::ReverseTime, "2020-03-15 09:30", false ],
            "missing_hour"  => [ $input, [ "b", "missing" ], DateFormat::ReverseTime, "2020-03-15 00:00", false ],
            "empty_date"    => [ $input, [ "c", "a_h" ], DateFormat::ReverseTime, null, true ],
            "invalid_date"  => [ $input, [ "d", "missing" ], DateFormat::ReverseTime, null, true ],
            "invalid_hour"  => [ $input, [ "e", "e_h" ], DateFormat::ReverseTime, null, true ],
            "missing_both"  => [ $input, [ "missing", "missing" ], DateFormat::ReverseTime, null, true ],
            "empty_input"   => [ [], [ "missing", "missing" ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayMiddle")]
    public function testToDayMiddle(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayMiddle(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayMiddle(): array {
        $input = [
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ];
        return [
            "iso"         => [ $input, [ "a" ], DateFormat::ReverseTime, "2020-03-15 12:00", false ],
            "dmy"         => [ $input, [ "b" ], DateFormat::ReverseTime, "2020-03-15 12:00", false ],
            "empty"       => [ $input, [ "c" ], DateFormat::ReverseTime, null, true ],
            "invalid"     => [ $input, [ "d" ], DateFormat::ReverseTime, null, true ],
            "missing"     => [ $input, [ "missing" ], DateFormat::ReverseTime, null, true ],
            "empty_input" => [ [], [ "missing" ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayEnd")]
    public function testToDayEnd(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayEnd(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayEnd(): array {
        $input = [
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ];
        return [
            "iso"         => [ $input, [ "a" ], DateFormat::ReverseTime, "2020-03-15 23:59", false ],
            "dmy"         => [ $input, [ "b" ], DateFormat::ReverseTime, "2020-03-15 23:59", false ],
            "empty"       => [ $input, [ "c" ], DateFormat::ReverseTime, null, true ],
            "invalid"     => [ $input, [ "d" ], DateFormat::ReverseTime, null, true ],
            "missing"     => [ $input, [ "missing" ], DateFormat::ReverseTime, null, true ],
            "empty_input" => [ [], [ "missing" ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerToDayEndHour")]
    public function testToDayEndHour(array $input, array $args, DateFormat $format, ?string $expected, bool $shouldBeEmpty): void {
        $result = (new Request($input))->toDayEndHour(...$args);
        $this->assertDateResult($result, $format, $expected, $shouldBeEmpty);
    }

    public static function providerToDayEndHour(): array {
        $input = [
            "a"   => "2020-03-15",
            "a_h" => "18:45",
            "b"   => "15-03-2020",
            "c"   => "",
            "d"   => "not-a-date",
            "e"   => "2020-03-16",
            "e_h" => "invalid-time",
        ];
        return [
            "date_and_hour" => [ $input, [ "a", "a_h" ], DateFormat::ReverseTime, "2020-03-15 18:45", false ],
            "missing_hour"  => [ $input, [ "b", "missing" ], DateFormat::ReverseTime, "2020-03-15 23:59", false ],
            "empty_date"    => [ $input, [ "c", "a_h" ], DateFormat::ReverseTime, null, true ],
            "invalid_date"  => [ $input, [ "d", "missing" ], DateFormat::ReverseTime, null, true ],
            "invalid_hour"  => [ $input, [ "e", "e_h" ], DateFormat::ReverseTime, null, true ],
            "missing_both"  => [ $input, [ "missing", "missing" ], DateFormat::ReverseTime, null, true ],
            "empty_input"   => [ [], [ "missing", "missing" ], DateFormat::ReverseTime, null, true ],
        ];
    }


    #[DataProvider("providerGetFile")]
    public function testGetFile(string $key, bool $isValid): void {
        $request = new Request();
        $file    = $request->getFile($key);
        $this->assertInstanceOf(File::class, $file);
        $this->assertSame($isValid, $file->isValid());
    }

    public static function providerGetFile(): array {
        return [
            "text"    => [ "f", true ],
            "image"   => [ "g", true ],
            "missing" => [ "missing", false ],
        ];
    }


    #[DataProvider("providerToArray")]
    public function testToArray(array $input): void {
        $this->assertSame($input, (new Request($input))->toArray());
    }

    public static function providerToArray(): array {
        return [
            "basic" => [[
                "a" => 1,
                "b" => "x",
                "c" => [ "k" => "v" ],
            ]],
            "empty" => [[]],
        ];
    }


    #[DataProvider("providerToDictionary")]
    public function testToDictionary(array $input): void {
        $dict = (new Request($input))->toDictionary();
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertSame($input, $dict->toArray());
    }

    public static function providerToDictionary(): array {
        return [
            "basic" => [[ "a" => 2, "b" => "y" ]],
            "empty" => [[], [] ],
        ];
    }


    #[DataProvider("providerGetIterator")]
    public function testGetIterator(array $input): void {
        $request = new Request($input);
        $collected = [];
        foreach ($request as $key => $value) {
            $collected[$key] = $value;
        }
        $this->assertSame($input, $collected);
    }

    public static function providerGetIterator(): array {
        return [
            "basic" => [[ "a" => 1, "b" => "x", "c" => [ "k" => "v" ]]],
            "empty" => [[]],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(array $input): void {
        $request = new Request($input);
        $this->assertSame($input, $request->jsonSerialize());
        $this->assertSame(JSON::encode($input), json_encode($request));
    }

    public static function providerJsonSerialize(): array {
        return [
            "basic" => [[ "a" => 1, "b" => "x" ]],
            "empty" => [[]],
        ];
    }
}
