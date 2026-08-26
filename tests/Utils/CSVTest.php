<?php
namespace Tests\Utils;

use Framework\Utils\CSV;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CSVTest extends TestCase {

    private string $tmpFile = "";

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }


    #[DataProvider("providerParse")]
    public function testParse(string $line, string $separator, array $expected): void {
        $this->assertEquals($expected, CSV::parse($line, $separator));
    }

    public static function providerParse(): array {
        return [
            "basic"            => [ "a,b,c", ",", [ "a", "b", "c" ] ],
            "empty fields"     => [ "a,,b", ",", [ "a", "", "b" ] ],
            "custom separator" => [ "a;b;c", ";", [ "a", "b", "c" ] ],
            "quoted fields"    => [ "\"a,b\",c", ",", [ "a,b", "c" ] ],
        ];
    }


    #[DataProvider("providerEncode")]
    public function testEncode(mixed $input, string $separator, string $expected): void {
        $this->assertEquals($expected, CSV::encode($input, $separator));
    }

    public static function providerEncode(): array {
        return [
            "array basic"            => [ ["a", "b", "c"], ",", "a,b,c" ],
            "zero cell kept"         => [ ["a", "0", "b"], ",", "a,0,b" ],
            "zero string kept"       => [ "a,0,b", ",", "a,0,b" ],
            "array skip empty"       => [ ["a", "", "b"], ",", "a,b" ],
            "empty string"           => [ "", ",", "" ],
            "array semicolon"        => [ ["x", "y"], ";", "x;y" ],
            "string skip commas"     => [ "a,,b", ",", "a,b" ],
            "string skip semicolons" => [ "a;;b", ";", "a;b" ],
            "string diff separator"  => [ "a,b", ";", "a,b" ],
            "array empty separator"  => [ ["a", "b"], "", "ab" ],
            "string empty separator" => [ "a,b", "", "a,b" ],
            "simple string"          => [ "abc", "", "abc" ],
        ];
    }


    #[DataProvider("providerDecode")]
    public function testDecode(mixed $input, string $separator, array $fields, mixed $expected): void {
        $this->assertEquals($expected, CSV::decode($input, $separator, $fields));
    }

    public static function providerDecode(): array {
        return [
            "string basic"         => [ "a,b,c", ",", [], ["a", "b", "c"] ],
            "numeric strings"      => [ "1,2,3", ",", [], ["1", "2", "3"] ],
            "array passthrough"    => [ ["x", "y"], ",", [], ["x", "y"] ],
            "custom separator"     => [ "a;b", ";", [], ["a", "b"] ],
            "array with fields"    => [ ["1", "2"], ",", ["x", "y"], ["x" => "1", "y" => "2"] ],
            "empty separator"      => [ "a,b", "", [], ["a,b"] ],
            "empty separator semi" => [ "a;b", "", [], ["a;b"] ],
            "multi char separator" => [ "abc<>abs", "<>", [], ["abc<>abs"] ],
        ];
    }


    #[DataProvider("providerDecodeFile")]
    public function testDecodeFile(string $input, array $expected): void {
        $res = CSV::decodeFile($input);
        $this->assertIsArray($res);
        $this->assertCount(count($expected), $res);
        foreach ($expected as $index => $row) {
            $this->assertEquals($row, $res[$index]);
        }
    }

    public static function providerDecodeFile(): array {
        return [
            "basic multiline"  => [ "a,b\nc,d", [["a", "b"], ["c", "d"]] ],
            "trailing newline" => [ "a,b\nc,d\n", [["a", "b"], ["c", "d"]] ],
            "empty lines"      => [ "a,b\n\nc,d\n\n", [["a", "b"], ["c", "d"]] ],
            "quoted fields"    => [ "\"a,b\",c\n\"d,e\",f", [["a,b", "c"], ["d,e", "f"]] ],
        ];
    }


    #[DataProvider("providerReadFileAndWriteFile")]
    public function testReadFileAndWriteFile(bool $validFile, array $data, array $expected): void {
        $this->tmpFile = sys_get_temp_dir() . "/csv_utils_test_" . uniqid() . ".csv";
        if ($validFile) {
            touch($this->tmpFile);
        }

        $ok = CSV::writeFile($this->tmpFile, $data);
        $this->assertSame($validFile, $ok);

        $read = CSV::readFile($this->tmpFile);
        $this->assertEquals($expected, $read);
    }

    public static function providerReadFileAndWriteFile(): array {
        return [
            "basic write read" => [ true, [["p", "q"], ["r", "s"]], [["p", "q"], ["r", "s"]] ],
            "empty fields"     => [ true, [["a", ""], ["", "b"]], [["a"], ["b"]] ],
            "invalid file"     => [ false, [["x", "y"]], [] ],
        ];
    }


    #[DataProvider("providerReadFileSkipHeader")]
    public function testReadFileSkipHeader(string $data, array $expected): void {
        $this->tmpFile = sys_get_temp_dir() . "/csv_utils_test_" . uniqid() . ".csv";
        file_put_contents($this->tmpFile, $data);

        $res = CSV::readFile($this->tmpFile, ",", true);
        $this->assertIsArray($res);
        $this->assertCount(count($expected), $res);
        $this->assertEquals($expected, $res);
    }

    public static function providerReadFileSkipHeader(): array {
        return [
            "basic skip header" => [ "h1,h2\nv1,v2\nv3,v4\n", [["v1", "v2"], ["v3", "v4"]] ],
            "only header"       => [ "h1,h2\n", [] ],
            "empty lines"       => [ "h1,h2\n\nv1,v2\n\nv3,v4\n\n", [["v1", "v2"], ["v3", "v4"]] ],
        ];
    }
}
