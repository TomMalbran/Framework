<?php
namespace Tests\IO;

use Framework\IO\Request;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use CURLFile;

class RequestTest extends TestCase {
    use TestHelpers;

    protected string $tmpFileF = "";
    protected string $tmpFileG = "";
    protected string $tmpFileH = "";


    protected function setUp(): void {
        $tmpDir = sys_get_temp_dir();
        $this->tmpFileF = $tmpDir . DIRECTORY_SEPARATOR . "req_test_f_" . uniqid();
        $this->tmpFileG = $tmpDir . DIRECTORY_SEPARATOR . "req_test_g_" . uniqid() . ".png";
        $this->tmpFileH = $tmpDir . DIRECTORY_SEPARATOR . "req_test_h_" . uniqid();

        @file_put_contents($this->tmpFileF, "hello");

        $pngData = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAn8B9o7s3wAAAABJRU5ErkJggg==");
        @file_put_contents($this->tmpFileG, $pngData);

        @file_put_contents($this->tmpFileH, "");

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
        if ($this->tmpFileF !== "" && file_exists($this->tmpFileF)) {
            @unlink($this->tmpFileF);
        }
        if ($this->tmpFileG !== "" && file_exists($this->tmpFileG)) {
            @unlink($this->tmpFileG);
        }
        if ($this->tmpFileH !== "" && file_exists($this->tmpFileH)) {
            @unlink($this->tmpFileH);
        }

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


    #[DataProvider("providerIsValidString")]
    public function testIsValidString(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidString(...$args));
    }

    public static function providerIsValidString(): array {
        $input = [
            "a" => "ok",
            "b" => "",
            "c" => null,
            "d" => "   ",
            "e" => "  x  ",
        ];
        return [
            "valid"       => [ $input, [ "a" ], true ],
            "empty"       => [ $input, [ "b" ], false ],
            "null"        => [ $input, [ "c" ], false ],
            "spaces"      => [ $input, [ "d" ], false ],
            "trimmed"     => [ $input, [ "e" ], true ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidLength")]
    public function testIsValidLength(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidLength(...$args));
    }

    public static function providerIsValidLength(): array {
        $input = [ "a" => "abcd", "b" => "abcde", "c" => "  abc  " ];
        return [
            "exact"       => [ $input, [ "a", 4 ], true ],
            "long"        => [ $input, [ "b", 4 ], false ],
            "trimmed"     => [ $input, [ "c", 4 ], true ],
            "missing"     => [ $input, [ "missing", 4 ], true ],
            "empty_input" => [ [], [ "missing", 4 ], true ],
        ];
    }


    #[DataProvider("providerIsValidNumber")]
    public function testIsValidNumber(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidNumber(...$args));
    }

    public static function providerIsValidNumber(): array {
        $input = [
            "a" => "12",
            "b" => "12.5",
            "c" => 3,
            "d" => 3.5,
            "e" => "x",
            "z" => "0",
        ];
        return [
            "integer"     => [ $input, [ "a" ], true ],
            "float"       => [ $input, [ "b" ], true ],
            "int_val"     => [ $input, [ "c" ], true ],
            "flt_val"     => [ $input, [ "d" ], true ],
            "invalid"     => [ $input, [ "e" ], false ],
            "zero"        => [ $input, [ "z" ], true ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsNumeric")]
    public function testIsNumeric(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isNumeric(...$args));
    }

    public static function providerIsNumeric(): array {
        $input = [
            "a" => "5",
            "b" => "00",
            "c" => "-3",
            "d" => "x",
            "e" => "1.5",
            "f" => "123",
        ];
        return [
            "min_pass"         => [ $input, [ "a", 1 ], true ],
            "min_fail"         => [ $input, [ "a", 7 ], false ],
            "leading_zeroes"   => [ $input, [ "b" ], false ],
            "zero_allowed"     => [ $input, [ "b", 0 ], true ],
            "negative_fail"    => [ $input, [ "c" ], false ],
            "negative_ok"      => [ $input, [ "c", null ], true ],
            "alpha"            => [ $input, [ "d" ], false ],
            "decimal"          => [ $input, [ "e" ], true ],
            "missing"          => [ $input, [ "missing" ], false ],
            "range_pass"       => [ $input, [ "f", 1, 200 ], true ],
            "range_fail"       => [ $input, [ "f", 1, 2 ], false ],
            "decimals_allowed" => [ $input, [ "e", null, null, 1 ], true ],
            "decimals_blocked" => [ $input, [ "e", null, null, 0 ], false ],
            "negative_int_ok"  => [ $input, [ "c", null, null, 0 ], true ],
            "empty_input"      => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidPrice")]
    public function testIsValidPrice(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidPrice(...$args));
    }

    public static function providerIsValidPrice(): array {
        $input = [
            "a" => "10.50",
            "b" => "10",
            "c" => "x",
            "d" => "",
            "e" => "0.50",
            "f" => "200",
            "g" => "1.234",
            "h" => "-1.00",
        ];
        return [
            "decimal"           => [ $input, [ "a" ], true ],
            "integer"           => [ $input, [ "b" ], true ],
            "invalid"           => [ $input, [ "c" ], false ],
            "empty"             => [ $input, [ "d" ], false ],
            "missing"           => [ $input, [ "missing" ], false ],
            "below_default_min" => [ $input, [ "e" ], false ],
            "no_min"            => [ $input, [ "e", null ], true ],
            "zero_min"          => [ $input, [ "e", 0 ], true ],
            "within_range"      => [ $input, [ "f", 1, 500 ], true ],
            "above_range"       => [ $input, [ "f", 1, 100 ], false ],
            "too_precise"       => [ $input, [ "g" ], false ],
            "negative"          => [ $input, [ "h" ], false ],
            "empty_input"       => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsAlphaNum")]
    public function testIsAlphaNum(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isAlphaNum(...$args));
    }

    public static function providerIsAlphaNum(): array {
        $input = [
            "a" => "abc-123",
            "b" => "abc123",
            "c" => "abc_123",
            "d" => "",
            "e" => null,
        ];
        return [
            "dash_allowed"   => [ $input, [ "a", true ], true ],
            "dash_blocked"   => [ $input, [ "a", false ], false ],
            "plain"          => [ $input, [ "b", false ], true ],
            "underscore_ok"  => [ $input, [ "c", true ], true ],
            "dash_len_ok"    => [ $input, [ "a", true, 7 ], true ],
            "dash_len_fail"  => [ $input, [ "a", true, 6 ], false ],
            "plain_len_ok"   => [ $input, [ "b", false, 6 ], true ],
            "under_len_ok"   => [ $input, [ "c", true, 7 ], true ],
            "under_len_fail" => [ $input, [ "c", true, 8 ], false ],
            "empty"          => [ $input, [ "d", true ], false ],
            "null"           => [ $input, [ "e", true ], false ],
            "missing"        => [ $input, [ "missing", true ], false ],
            "empty_input"    => [ [], [ "missing", true ], false ],
        ];
    }


    #[DataProvider("providerIsValidEmail")]
    public function testIsValidEmail(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidEmail(...$args));
    }

    public static function providerIsValidEmail(): array {
        $input = [
            "a" => "test@example.com",
            "b" => "invalid-email",
            "c" => "",
            "d" => null,
        ];
        return [
            "valid"       => [ $input, [ "a" ], true ],
            "invalid"     => [ $input, [ "b" ], false ],
            "empty"       => [ $input, [ "c" ], false ],
            "null"        => [ $input, [ "d" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidPassword")]
    public function testIsValidPassword(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidPassword(...$args));
    }

    public static function providerIsValidPassword(): array {
        $input = [
            "a" => "abc123",
            "b" => "aBc123",
            "c" => "abcdef",
            "d" => "a1b2c3",
            "e" => "ab12",
            "f" => "",
        ];
        return [
            "default"       => [ $input, [ "a" ], true ],
            "all_rules"     => [ $input, [ "b", "lud" ], true ],
            "upper_digit"   => [ $input, [ "b", "ud", 5 ], true ],
            "letters_only"  => [ $input, [ "c" ], false ],
            "missing_upper" => [ $input, [ "d", "lud" ], false ],
            "too_short"     => [ $input, [ "e", "lud", 5 ], false ],
            "empty"         => [ $input, [ "f" ], false ],
            "missing"       => [ $input, [ "missing" ], false ],
            "empty_input"   => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidUsername")]
    public function testIsValidUsername(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidUsername(...$args));
    }

    public static function providerIsValidUsername(): array {
        $input = [
            "a" => "user1",
            "b" => "2user",
            "c" => "user-3",
            "d" => "423",
            "e" => "user_5",
            "f" => "-user6",
            "g" => "user7-",
            "h" => "",
        ];
        return [
            "letters"       => [ $input, [ "a" ], true ],
            "leading_digit" => [ $input, [ "b" ], true ],
            "hyphen"        => [ $input, [ "c" ], true ],
            "numeric"       => [ $input, [ "d" ], true ],
            "underscore"    => [ $input, [ "e" ], false ],
            "starts_hyphen" => [ $input, [ "f" ], false ],
            "ends_hyphen"   => [ $input, [ "g" ], false ],
            "empty"         => [ $input, [ "h" ], false ],
            "missing"       => [ $input, [ "missing" ], false ],
            "empty_input"   => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidColor")]
    public function testIsValidColor(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidColor(...$args));
    }

    public static function providerIsValidColor(): array {
        $input = [
            "a" => "#fff",
            "b" => "#ffffff",
            "c" => "fff",
            "d" => "rgb(255,0,0)",
            "e" => "rgba(255,0,0,0.5)",
            "f" => "hsl(120,100%,50%)",
            "g" => "not-a-color",
            "h" => "",
            "i" => null,
        ];
        return [
            "short_hex"   => [ $input, [ "a" ], true ],
            "long_hex"    => [ $input, [ "b" ], true ],
            "no_hash"     => [ $input, [ "c" ], false ],
            "rgb"         => [ $input, [ "d" ], false ],
            "rgba"        => [ $input, [ "e" ], false ],
            "hsl"         => [ $input, [ "f" ], false ],
            "invalid"     => [ $input, [ "g" ], false ],
            "empty"       => [ $input, [ "h" ], false ],
            "null"        => [ $input, [ "i" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidCUIT")]
    public function testIsValidCUIT(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidCUIT(...$args));
    }

    public static function providerIsValidCUIT(): array {
        $input = [
            "a" => "20-12345678-6",
            "b" => "20123456786",
            "c" => "20 12345678 6",
            "d" => "20-12345678-0",
            "e" => "123",
            "f" => "",
            "g" => null,
        ];
        return [
            "formatted"   => [ $input, [ "a" ], true ],
            "plain"       => [ $input, [ "b" ], true ],
            "spaced"      => [ $input, [ "c" ], true ],
            "bad_check"   => [ $input, [ "d" ], false ],
            "short"       => [ $input, [ "e" ], false ],
            "empty"       => [ $input, [ "f" ], false ],
            "null"        => [ $input, [ "g" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidDNI")]
    public function testIsValidDNI(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidDNI(...$args));
    }

    public static function providerIsValidDNI(): array {
        $input = [
            "a" => "12.345.678",
            "b" => "12345678",
            "c" => "12 345 678",
            "d" => "123",
            "e" => "abc",
            "f" => "",
            "g" => null,
        ];
        return [
            "formatted"   => [ $input, [ "a" ], true ],
            "plain"       => [ $input, [ "b" ], true ],
            "spaced"      => [ $input, [ "c" ], true ],
            "short"       => [ $input, [ "d" ], false ],
            "alpha"       => [ $input, [ "e" ], false ],
            "empty"       => [ $input, [ "f" ], false ],
            "null"        => [ $input, [ "g" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidPhone")]
    public function testIsValidPhone(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidPhone(...$args));
    }

    public static function providerIsValidPhone(): array {
        $input = [
            "a" => "(123) 456-7890",
            "b" => "123-456-7890",
            "c" => "+1 123 456 7890",
            "d" => "1234567",
            "e" => "abc",
            "f" => "",
            "g" => null,
        ];
        return [
            "paren"       => [ $input, [ "a" ], true ],
            "dashes"      => [ $input, [ "b" ], true ],
            "intl"        => [ $input, [ "c" ], true ],
            "short"       => [ $input, [ "d" ], true ],
            "alpha"       => [ $input, [ "e" ], false ],
            "empty"       => [ $input, [ "f" ], false ],
            "null"        => [ $input, [ "g" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidUrl")]
    public function testIsValidUrl(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidUrl(...$args));
    }

    public static function providerIsValidUrl(): array {
        $input = [
            "a" => "https://example.com/path",
            "b" => "http://sub.example.com",
            "c" => "ftp://example.com",
            "d" => "example.com",
            "e" => "https://example.com:8080/path",
            "f" => "not-a-url",
            "g" => "",
            "h" => null,
        ];
        return [
            "https"       => [ $input, [ "a" ], true ],
            "http"        => [ $input, [ "b" ], true ],
            "ftp"         => [ $input, [ "c" ], false ],
            "host"        => [ $input, [ "d" ], false ],
            "port"        => [ $input, [ "e" ], true ],
            "invalid"     => [ $input, [ "f" ], false ],
            "empty"       => [ $input, [ "g" ], false ],
            "null"        => [ $input, [ "h" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidDomain")]
    public function testIsValidDomain(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidDomain(...$args));
    }

    public static function providerIsValidDomain(): array {
        $input = [
            "a" => "https://example.com/path",
            "b" => "http://sub.example.com",
            "c" => "www.example.com",
            "d" => "subdomain.example.co.uk",
            "e" => "not-a-domain",
            "f" => "",
            "g" => null,
        ];
        return [
            "url"         => [ $input, [ "a" ], true ],
            "sub_url"     => [ $input, [ "b" ], true ],
            "www"         => [ $input, [ "c" ], true ],
            "subdomain"   => [ $input, [ "d" ], true ],
            "invalid"     => [ $input, [ "e" ], false ],
            "empty"       => [ $input, [ "f" ], false ],
            "null"        => [ $input, [ "g" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidSlug")]
    public function testIsValidSlug(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidSlug(...$args));
    }

    public static function providerIsValidSlug(): array {
        $input = [
            "a" => "valid-slug",
            "b" => "valid-slug-123",
            "c" => "123",
            "d" => "invalid slug",
            "e" => "invalid_slug",
            "f" => "Invalid-Slug",
            "g" => "",
            "h" => null,
        ];
        return [
            "basic"       => [ $input, [ "a" ], true ],
            "numeric"     => [ $input, [ "b" ], true ],
            "digits"      => [ $input, [ "c" ], true ],
            "spaces"      => [ $input, [ "d" ], false ],
            "underscore"  => [ $input, [ "e" ], false ],
            "caps"        => [ $input, [ "f" ], false ],
            "empty"       => [ $input, [ "g" ], false ],
            "null"        => [ $input, [ "h" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidPosition")]
    public function testIsValidPosition(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidPosition(...$args));
    }

    public static function providerIsValidPosition(): array {
        $input = [
            "a" => "0",
            "b" => "1",
            "c" => "-1",
            "d" => "xh",
            "e" => "",
            "f" => null,
        ];
        return [
            "zero"        => [ $input, [ "a" ], true ],
            "one"         => [ $input, [ "b" ], true ],
            "negative"    => [ $input, [ "c" ], false ],
            "alpha"       => [ $input, [ "d" ], true ],
            "empty"       => [ $input, [ "e" ], true ],
            "null"        => [ $input, [ "f" ], true ],
            "missing"     => [ $input, [ "missing" ], true ],
            "empty_input" => [ [], [ "missing" ], true ],
        ];
    }


    #[DataProvider("providerIsValidDate")]
    public function testIsValidDate(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidDate(...$args));
    }

    public static function providerIsValidDate(): array {
        $input = [
            "a" => "2020-01-01",
            "b" => "2020-02-29",
            "c" => "2021-02-29",
            "d" => "01-01-2020",
            "e" => "",
            "f" => null,
        ];
        return [
            "iso"         => [ $input, [ "a" ], true ],
            "leap"        => [ $input, [ "b" ], true ],
            "invalid"     => [ $input, [ "c" ], true ],
            "dmy"         => [ $input, [ "d" ], true ],
            "empty"       => [ $input, [ "e" ], false ],
            "null"        => [ $input, [ "f" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidHour")]
    public function testIsValidHour(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidHour(...$args));
    }

    public static function providerIsValidHour(): array {
        $input = [
            "a" => "00:00",
            "b" => "12:00",
            "c" => "23:59",
            "d" => "24:00",
            "e" => "12",
            "f" => "",
            "g" => null,
            "h" => "12:30",
            "i" => "12:15",
            "j" => "05:00",
            "k" => "04:59",
            "l" => "20:00",
            "m" => "21:00",
            "n" => "05:60",
        ];
        return [
            "midnight"     => [ $input, [ "a" ], true ],
            "noon"         => [ $input, [ "b" ], true ],
            "end_of_day"   => [ $input, [ "c" ], true ],
            "too_late"     => [ $input, [ "d" ], false ],
            "no_minutes"   => [ $input, [ "e" ], false ],
            "empty"        => [ $input, [ "f" ], false ],
            "null"         => [ $input, [ "g" ], false ],
            "missing"      => [ $input, [ "missing" ], false ],
            "allowed_step" => [ $input, [ "h", [ 0, 30 ] ], true ],
            "bad_step"     => [ $input, [ "i", [ 0, 30 ] ], false ],
            "min_hour"     => [ $input, [ "j", null, 5 ], true ],
            "before_min"   => [ $input, [ "k", null, 5 ], false ],
            "max_hour"     => [ $input, [ "l", null, 0, 20 ], true ],
            "after_max"    => [ $input, [ "m", null, 0, 20 ], false ],
            "bad_minutes"  => [ $input, [ "n" ], false ],
            "empty_input"  => [ [], [ "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidPeriod")]
    public function testIsValidPeriod(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidPeriod(...$args));
    }

    public static function providerIsValidPeriod(): array {
        $input = [
            "a" => "2020-01-01",
            "b" => "2020-01-02",
            "c" => "2020-01-02",
            "d" => "2020-01-01",
            "e" => "2021-02-29",
            "f" => "01-01-2020",
        ];
        return [
            "ascending"     => [ $input, [ "a", "b" ], true ],
            "same_day"      => [ $input, [ "a", "a" ], true ],
            "mixed_formats" => [ $input, [ "a", "f" ], true ],
            "descending"    => [ $input, [ "c", "d" ], false ],
            "invalid_date"  => [ $input, [ "e", "b" ], false ],
            "missing_start" => [ $input, [ "missing", "b" ], false ],
            "missing_end"   => [ $input, [ "a", "missing" ], false ],
            "empty_input"   => [ [], [ "missing", "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidHourPeriod")]
    public function testIsValidHourPeriod(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidHourPeriod(...$args));
    }

    public static function providerIsValidHourPeriod(): array {
        $input = [
            "a" => "08:00", "b" => "10:00",
            "c" => "10:00", "d" => "08:00",
            "e" => "08:00", "f" => "08:00",
            "h" => "24:00", "i" => "25:00",
            "j" => "23:00", "k" => "01:00",
        ];
        return [
            "ascending"      => [ $input, [ "a", "b" ], true ],
            "descending"     => [ $input, [ "c", "d" ], false ],
            "same_hour"      => [ $input, [ "e", "f" ], false ],
            "invalid_hours"  => [ $input, [ "h", "i" ], false ],
            "cross_midnight" => [ $input, [ "j", "k" ], false ],
            "missing_start"  => [ $input, [ "missing", "b" ], false ],
            "missing_end"    => [ $input, [ "a", "missing" ], false ],
            "empty_input"    => [ [], [ "missing", "missing" ], false ],
        ];
    }


    #[DataProvider("providerIsValidWeekDay")]
    public function testIsValidWeekDay(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isValidWeekDay(...$args));
    }

    public static function providerIsValidWeekDay(): array {
        $input = [
            "a" => 0,
            "b" => 6,
            "c" => -1,
            "d" => 7,
            "e" => "3",
            "f" => null,
            "sm1" => 1,
            "sm7" => 7,
            "sm0" => 0,
        ];
        return [
            "zero_based_min"   => [ $input, [ "a" ], true ],
            "zero_based_max"   => [ $input, [ "b" ], true ],
            "negative"         => [ $input, [ "c" ], false ],
            "too_high"         => [ $input, [ "d" ], false ],
            "string"           => [ $input, [ "e" ], true ],
            "missing_default"  => [ $input, [ "missing" ], true ],
            "null_default"     => [ $input, [ "f" ], true ],
            "sunday_mode_min"  => [ $input, [ "sm1", true ], true ],
            "sunday_mode_max"  => [ $input, [ "sm7", true ], true ],
            "sunday_mode_zero" => [ $input, [ "sm0", true ], false ],
            "sunday_mode_str"  => [ $input, [ "e", true ], true ],
            "missing_sunday"   => [ $input, [ "missing", true ], false ],
            "empty_input"      => [ [], [ "missing", true ], false ],
        ];
    }


    #[DataProvider("providerIsFutureDate")]
    public function testIsFutureDate(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->isFutureDate(...$args));
    }

    public static function providerIsFutureDate(): array {
        $input = [
            "future" => "2099-01-01",
            "past"   => "2000-01-01",
        ];
        return [
            "future"      => [ $input, [ "future" ], true ],
            "past"        => [ $input, [ "past" ], false ],
            "missing"     => [ $input, [ "missing" ], false ],
            "date_type"   => [ $input, [ "future", DateType::Start ], true ],
            "empty_input" => [ [], [ "missing" ], false ],
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
    public function testGetFile(string $key, ?string $expectedKey): void {
        $request = new Request(withFiles: true);
        $result = $request->getFile($key);
        if ($expectedKey === null) {
            $this->assertNull($result);
            return;
        }
        $this->assertSame($_FILES[$expectedKey], $result);
    }

    public static function providerGetFile(): array {
        return [
            "text"    => [ "f", "f" ],
            "image"   => [ "g", "g" ],
            "missing" => [ "missing", null ],
        ];
    }


    #[DataProvider("providerGetFileName")]
    public function testGetFileName(string $key, string $expected): void {
        $request = new Request(withFiles: true);
        $this->assertSame($expected, $request->getFileName($key));
    }

    public static function providerGetFileName(): array {
        return [
            "text"    => [ "f", "a.txt" ],
            "image"   => [ "g", "image.PNG" ],
            "missing" => [ "missing", "" ],
        ];
    }


    #[DataProvider("providerGetFileType")]
    public function testGetFileType(string $key, string $expected): void {
        $request = new Request(withFiles: true);
        $this->assertSame($expected, $request->getFileType($key));
    }

    public static function providerGetFileType(): array {
        return [
            "text"    => [ "f", "text/plain" ],
            "image"   => [ "g", "image/png" ],
            "missing" => [ "missing", "" ],
        ];
    }


    #[DataProvider("providerGetTmpName")]
    public function testGetTmpName(string $key, string $expected): void {
        $request = new Request(withFiles: true);
        $result = $request->getTmpName($key);
        if (str_starts_with($expected, "tmpFile")) {
            $this->assertSame($this->{$expected}, $result);
            return;
        }
        $this->assertSame($expected, $result);
    }

    public static function providerGetTmpName(): array {
        return [
            "text"    => [ "f", "tmpFileF" ],
            "image"   => [ "g", "tmpFileG" ],
            "missing" => [ "missing", "" ],
        ];
    }


    #[DataProvider("providerGetCurlFile")]
    public function testGetCurlFile(string $key, ?array $expected): void {
        $request = new Request(withFiles: true);
        $curl = $request->getCurlFile($key);
        if ($expected === null) {
            $this->assertNull($curl);
            return;
        }

        $this->assertInstanceOf(CURLFile::class, $curl);
        $this->assertSame($this->{$expected["path"]}, $curl->getFilename());
        $this->assertSame($expected["mime"], $curl->getMimeType());
        $this->assertSame($expected["name"], $curl->getPostFilename());
    }

    public static function providerGetCurlFile(): array {
        return [
            "text" => [
                "f",
                [
                    "path" => "tmpFileF",
                    "mime" => "text/plain",
                    "name" => "a.txt",
                ],
            ],
            "missing" => [ "missing", null ],
        ];
    }


    #[DataProvider("providerHasFile")]
    public function testHasFile(array $args, bool $expected): void {
        $request = new Request(withFiles: true);
        $this->assertSame($expected, $request->hasFile(...$args));
    }

    public static function providerHasFile(): array {
        return [
            "text"        => [ [ "f" ], true ],
            "image"       => [ [ "g" ], true ],
            "missing"     => [ [ "missing" ], false ],
            "empty_input" => [ [ "" ], false ],
        ];
    }


    #[DataProvider("providerHasSizeError")]
    public function testHasSizeError(array $args, bool $expected): void {
        $request = new Request(withFiles: true);
        $this->assertSame($expected, $request->hasSizeError(...$args));
    }

    public static function providerHasSizeError(): array {
        return [
            "text"        => [ [ "f" ], false ],
            "size_error"  => [ [ "h" ], true ],
            "missing"     => [ [ "missing" ], true ],
            "empty_input" => [ [ "" ], true ],
        ];
    }


    #[DataProvider("providerHasExtension")]
    public function testHasExtension(array $input, array $args, bool $expected): void {
        $request = new Request($input, withFiles: true);
        $this->assertSame($expected, $request->hasExtension(...$args));
    }

    public static function providerHasExtension(): array {
        $input = [ "img" => "photo.jpg" ];
        return [
            "file_string" => [ $input, [ "f", "txt" ], true ],
            "file_array"  => [ $input, [ "g", [ "png" ] ], true ],
            "request"     => [ $input, [ "img", "jpg" ], true ],
            "missing"     => [ $input, [ "missing", "txt" ], false ],
            "multiple"    => [ $input, [ "multiple", "jpg", "png" ], false ],
            "empty_input" => [ [], [ "missing", "txt" ], false ],
        ];
    }


    #[DataProvider("providerIsValidImage")]
    public function testIsValidImage(array $input, array $args, bool $expected): void {
        $request = new Request($input, withFiles: true);
        $result  = $this->runWithSuppressedWarnings(
            fn() => $request->isValidImage(...$args),
            suppress: true,
        );

        $this->assertSame($expected, $result);
    }

    public static function providerIsValidImage(): array {
        $input = [ "img" => "image.png" ];
        return [
            "text_file"   => [ $input, [ "f" ], false ],
            "png_file"    => [ $input, [ "g" ], true ],
            "request"     => [ $input, [ "img" ], true ],
            "missing"     => [ $input, [ "missing" ], false ],
            "empty_input" => [ [], [ "missing" ], false ],
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
