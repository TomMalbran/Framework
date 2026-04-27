<?php
namespace Tests\Utils;

use Framework\Utils\JSON;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class JSONTest extends TestCase {

    private string $tmpFile = "";

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }


    #[DataProvider("providerIsValid")]
    public function testIsValid(mixed $json, bool $expected): void {
        $this->assertEquals($expected, JSON::isValid($json));
    }

    public static function providerIsValid(): array {
        return [
            "valid_object"   => [ '{"a":1}', true ],
            "valid_array"    => [ '["x","y"]', true ],
            "invalid_string" => [ 'not json', false ],
            "empty_string"   => [ "", false ],
            "numeric_input"  => [ 123, false ],
            "null_input"     => [ null, false ],
            "numeric_string" => [ "123", false ],
        ];
    }


    #[DataProvider("providerEncodeDecode")]
    public function testEncodeDecode(mixed $input, string $expectedEncoded, mixed $expectedDecoded): void {
        $encoded = JSON::encode($input);
        $this->assertEquals($expectedEncoded, $encoded);

        if ($expectedDecoded !== null) {
            $decoded = JSON::decode($encoded);
            $this->assertEquals($expectedDecoded, $decoded);
        }
    }

    public static function providerEncodeDecode(): array {
        return [
            "null_input"     => [ null, "", null ],
            "empty_string"   => [ "", "", null ],
            "stdClass"       => [ new stdClass(), "{}", [] ],
            "numeric_string" => [ "123", "123", null ],
            "normal_array"   => [ [ "x" => "y", "n" => 2 ], '{"x":"y","n":2}', [ "x" => "y", "n" => 2 ] ],
        ];
    }


    #[DataProvider("providerDecodeAsArray")]
    public function testDecodeAsArray(mixed $input, array $expected): void {
        $result = JSON::decodeAsArray($input);
        $this->assertEquals($expected, $result);
    }

    public static function providerDecodeAsArray(): array {
        return [
            "valid_json_object" => [ '{"a":"b","c":3}', [ "a" => "b", "c" => 3 ] ],
            "invalid_json"      => [ "not json", [] ],
            "numeric_input"     => [ 123, [] ],
            "null_input"        => [ null, [] ],
            "array_input"       => [ [ "x" => "y" ], [ "x" => "y" ] ],
        ];
    }


    #[DataProvider("providerDecodeAsDictionary")]
    public function testDecodeAsDictionary(mixed $json, string $expectedA, int $expectedC): void {
        $dict = JSON::decodeAsDictionary($json);
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertEquals($expectedA, $dict->getString("a"));
        $this->assertEquals($expectedC, $dict->getInt("c"));
    }

    public static function providerDecodeAsDictionary(): array {
        return [
            "valid_json_object" => [ '{"a":"b","c":3}', "b", 3 ],
            "invalid_json"      => [ "not json", "", 0 ],
            "numeric_input"     => [ 123, "", 0 ],
        ];
    }


    #[DataProvider("providerDecodeAsStrings")]
    public function testDecodeAsStrings(mixed $input, bool $skipEmpty, array $expected): void {
        $result = JSON::decodeAsStrings($input, $skipEmpty);
        $this->assertEquals($expected, $result);
    }

    public static function providerDecodeAsStrings(): array {
        return [
            "json_array_with_empty"  => [ '["one", "", "two"]', false, [ "one", "", "two" ] ],
            "json_array_skip_empty"  => [ '["one", "", "two"]', true, [ "one", "two" ] ],
            "numeric_json_array"     => [ "[1,2]", false, [ "1", "2" ] ],
            "array_input_with_empty" => [ [ 'a', '', 'b' ], false, [ "a", "", "b" ] ],
            "array_input_skip_empty" => [ [ 'a', '', 'b' ], true, [ "a", "b" ] ],
            "empty_string_input"     => [ "", true, [] ],
            "plain_string_input"     => [ "plain string", false, [] ],
        ];
    }


    #[DataProvider("providerFromCSV")]
    public function testFromCSV(string $csv, array $expectedStrings): void {
        $csvJson = JSON::fromCSV($csv);
        foreach ($expectedStrings as $expected) {
            $this->assertStringContainsString($expected, $csvJson);
        }
    }

    public static function providerFromCSV(): array {
        return [
            "simple_csv"      => [ "a,b,c", [ '"a"', '"b"', '"c"' ] ],
            "csv_with_spaces" => [ " a, b, ,c ", [ '"a"', '"b"', '"c"' ] ],
        ];
    }


    #[DataProvider("providerReadFileAndWriteFile")]
    public function testReadFileAndWriteFile(array $data, string $key, mixed $expectedValue): void {
        $this->tmpFile = sys_get_temp_dir() . "/json_utils_test_" . uniqid() . ".json";

        $ok = JSON::writeFile($this->tmpFile, $data);
        $this->assertTrue($ok);
        $this->assertFileExists($this->tmpFile);

        $read = JSON::readFile($this->tmpFile);
        $this->assertIsArray($read);
        $this->assertEquals($expectedValue, $read[$key]);
    }

    public static function providerReadFileAndWriteFile(): array {
        return [
            "valid_data"   => [ [ "p" => "q", "num" => 5 ], "p", "q" ],
            "numeric_data" => [ [ "p" => "q", "num" => 5 ], "num", 5 ],
        ];
    }

    public function testReadFileNonExistent(): void {
        $this->tmpFile = sys_get_temp_dir() . "/json_utils_test_" . uniqid() . ".json";
        $res = JSON::readFile($this->tmpFile);
        $this->assertIsArray($res);
        $this->assertCount(0, $res);
    }


    #[DataProvider("providerReadUrl")]
    public function testReadUrl(string $url, array $expected): void {
        $fromUrl = JSON::readUrl($url);
        $this->assertEquals($expected, $fromUrl);
    }

    public static function providerReadUrl(): array {
        $data = json_encode([ "u" => "v" ]);
        $validUrl = "data://text/plain," . rawurlencode($data);

        return [
            "valid_url"   => [ $validUrl, [ "u" => "v" ] ],
            "invalid_url" => [ "invalid://url", [] ],
        ];
    }


    #[DataProvider("providerPostUrl")]
    public function testPostUrl(string $url, array $data, bool $shouldSucceed): void {
        $res = JSON::postUrl($url, $data);
        $this->assertIsArray($res);

        if ($shouldSucceed) {
            $this->assertArrayHasKey("ok", $res);
            $this->assertTrue($res["ok"]);
        } else {
            $this->assertEquals([], $res);
        }
    }

    public static function providerPostUrl(): array {
        return [
            "valid_url"   => [ "test://post/endpoint", [ "a" => "b", "n" => 3 ], true ],
            "invalid_url" => [ "invalid://url", [ "a" => "b" ], false ],
        ];
    }
}
