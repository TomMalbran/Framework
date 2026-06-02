<?php
namespace Tests\IO;

use Framework\IO\Request;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\File\File;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestRequestEnum implements Enum {
    use IsEnum;

    case None;
    case Key;
    case Value;
}

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


    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(mixed $input, bool $expected): void {
        $d = new Request($input);
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
        $d = new Request($input);
        $this->assertEquals($expected, $d->isNotEmpty());
    }

    public static function providerIsNotEmpty(): array {
        return [
            "empty"     => [ [], false ],
            "non_empty" => [ [ "a" => 1 ], true ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(array $input, Enum|string $key, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->has($key));
    }

    public static function providerHas(): array {
        $input = [ "a" => "", "b" => "1", "c" => null, "d" => 0, "Key" => "value" ];
        return [
            "empty_string"  => [ $input, "a", true ],
            "null"          => [ $input, "c", false ],
            "zero"          => [ $input, "d", true ],
            "missing"       => [ $input, "missing", false ],
            "enum_valid"    => [ $input, TestRequestEnum::Key, true ],
            "enum_missing"  => [ $input, TestRequestEnum::Value, false ],
        ];
    }


    #[DataProvider("providerHasValue")]
    public function testHasValue(array $input, Enum|string $key, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->hasValue($key));
    }

    public static function providerHasValue(): array {
        $input = [
            "a" => "",
            "b" => "1",
            "c" => [ "z" ],
            "d" => null,
            "e" => 0,
            "Key" => "value",
        ];
        return [
            "empty_string"  => [ $input, "a", false ],
            "string"        => [ $input, "b", true ],
            "array"         => [ $input, "c", true ],
            "null"          => [ $input, "d", false ],
            "missing"       => [ $input, "missing", false ],
            "empty_request" => [ [], "", false ],
            "enum_valid"    => [ $input, TestRequestEnum::Key, true ],
            "enum_missing"  => [ $input, TestRequestEnum::Value, false ],
        ];
    }


    #[DataProvider("providerSet")]
    public function testSet(array $operations, string $method, string $key, mixed $expected): void {
        $request = new Request();
        foreach ($operations as [ $setKey, $setValue ]) {
            $request->set($setKey, $setValue);
        }
        $this->assertSame($expected, $request->{$method}($key));
    }

    public static function providerSet(): array {
        $operations = [
            [ "x", 1 ],
            [ "x", "2" ],
            [ "arr", [ "", "b", null, 3 ] ],
            [ "n", null ],
        ];
        return [
            "get_value"   => [ $operations, "get", "x", "2" ],
            "get_null"    => [ $operations, "get", "n", "" ],
            "get_array"   => [ $operations, "get", "arr", [ "", "b", null, 3 ] ],
            "exists_null" => [ $operations, "has", "n", false ],
            "has_null"    => [ $operations, "hasValue", "n", false ],
        ];
    }


    #[DataProvider("providerRemove")]
    public function testRemove(array $input, array $removeKeys, string $method, array $args, mixed $expected): void {
        $request = new Request($input);
        foreach ($removeKeys as $key) {
            $request->remove($key);
        }
        $this->assertSame($expected, $request->{$method}(...$args));
    }

    public static function providerRemove(): array {
        $input = [ "a" => 1, "b" => "x" ];
        $removeKeys = [ "a", "missing" ];
        return [
            "removed_key" => [ $input, $removeKeys, "has", [ "a" ], false ],
            "missing_key" => [ $input, $removeKeys, "has", [ "missing" ], false ],
            "default_get" => [ $input, $removeKeys, "get", [ "a", "def" ], "def" ],
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


    #[DataProvider("providerGetBool")]
    public function testGetBool(array $input, array $args, bool $expected): void {
        $request = new Request($input);
        $this->assertSame($expected, $request->getBool(...$args));
    }

    public static function providerGetBool(): array {
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


    #[DataProvider("providerGetDate")]
    public function testGetDate(
        array $input,
        string $dateKey,
        string $hourKey,
        DateType $type,
        DateFormat $format,
        ?string $expected,
        bool $shouldBeEmpty,
    ): void {
        $result = (new Request($input))->getDate($dateKey, $hourKey, $type);
        if ($shouldBeEmpty) {
            $this->assertTrue($result->isEmpty());
        } else {
            $this->assertSame($expected, $result->toString($format));
        }
    }

    public static function providerGetDate(): array {
        $input = [
            "a" => "2020-01-01",
            "b" => "01-01-2020",
            "c" => "2020-02-29",
            "d" => "not-a-date",
            "e" => "",
            "h" => "12:34",
        ];
        return [
            "start"       => [ $input, "a", "", DateType::Start, DateFormat::ReverseTime, "2020-01-01 00:00", false ],
            "middle"      => [ $input, "a", "", DateType::Middle, DateFormat::ReverseTime, "2020-01-01 12:00", false ],
            "end"         => [ $input, "a", "", DateType::End, DateFormat::ReverseTime, "2020-01-01 23:59", false ],
            "dmy"         => [ $input, "b", "", DateType::Start, DateFormat::ReverseTime, "2020-01-01 00:00", false ],
            "leap"        => [ $input, "c", "", DateType::Start, DateFormat::ReverseTime, "2020-02-29 00:00", false ],
            "invalid"     => [ $input, "d", "", DateType::Start, DateFormat::ReverseTime, null, true ],
            "empty"       => [ $input, "e", "", DateType::Start, DateFormat::ReverseTime, null, true ],
            "with_hour"   => [ $input, "a", "h", DateType::Start, DateFormat::ReverseTime, "2020-01-01 12:34", false ],
            "hour_only"   => [ $input, "missing", "h", DateType::Start, DateFormat::ReverseTime, "", true ],
            "missing"     => [ $input, "missing", "", DateType::Start, DateFormat::ReverseTime, null, true ],
            "empty_input" => [ [], "missing", "", DateType::Start, DateFormat::ReverseTime, null, true ],
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


    #[DataProvider("providerGetDict")]
    public function testGetDict(array $input, string $key, array $expected): void {
        $result = (new Request($input))->getDict($key);
        $this->assertInstanceOf(Dictionary::class, $result);
        $this->assertSame($expected, $result->toArray());
    }

    public static function providerGetDict(): array {
        $input = [
            "a" => JSON::encode([ "x" => 1 ]),
            "b" => 123,
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
