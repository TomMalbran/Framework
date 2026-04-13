<?php
namespace Tests\Utils;

use Framework\Utils\CSV;

use PHPUnit\Framework\TestCase;

class CSVTest extends TestCase {

    private string $tmpFile = "";

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }

    public function testEncode() {
        $arr = [ "a", "b", "c" ];
        $this->assertEquals("a,b,c", CSV::encode($arr));

        // arrays remove empty values
        $this->assertEquals("a,b", CSV::encode([ "a", "", "b" ]));

        // empty string becomes empty
        $this->assertEquals("", CSV::encode(""));

        // custom separator
        $this->assertEquals("x;y", CSV::encode([ "x","y" ], ";"));

        // encode a string with separator should remove empty parts
        $this->assertEquals("a,b", CSV::encode("a,,b"));
        $this->assertEquals("a;b", CSV::encode("a;;b", ";"));

        // encode with an invalid separator
        $this->assertEquals("a,b", CSV::encode("a,b", ";"));

        // encode with empty separator is fine with arrays
        $this->assertEquals("ab", CSV::encode([ "a", "b" ], ""));

        // encode with empty separator should return original string
        $this->assertEquals("a,b", CSV::encode("a,b", ""));
        $this->assertEquals("abc", CSV::encode("abc", ""));
    }

    public function testDecode() {
        $decoded = CSV::decode("a,b,c");
        $this->assertIsArray($decoded);
        $this->assertEquals(["a", "b", "c"], array_values($decoded));

        // numeric values remain strings
        $this->assertEquals([ "1", "2", "3" ], CSV::decode("1,2,3"));

        // array input is returned as-is
        $this->assertEquals([ "x", "y" ], CSV::decode([ "x", "y" ]));

        // custom separator for decode
        $this->assertEquals([ "a","b" ], CSV::decode("a;b", ";"));

        // decode an array with fields should return associative array
        $res = CSV::decode([ "1", "2" ], ",", [ "x", "y" ]);
        $this->assertIsArray($res);
        $this->assertArrayHasKey("x", $res);
        $this->assertEquals("1", $res["x"]);
        $this->assertEquals("2", $res["y"]);

        // decode with empty separator returns original string in an array
        $this->assertEquals([ "a,b" ], CSV::decode("a,b", ""));
        $this->assertEquals([ "a;b" ], CSV::decode("a;b", ""));

        // decode with separator with more than 1 character should return original string in array
        $this->assertEquals([ "abc<>abs" ], CSV::decode("abc<>abs", "<>"));
    }

    public function testDecodeFile() {
        $input = "a,b\nc,d";
        $res = CSV::decode($input);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals([ "a", "b" ], $res[0]);
        $this->assertEquals([ "c", "d" ], $res[1]);
    }

    public function testReadFileAndWriteFile() {
        $this->tmpFile = sys_get_temp_dir() . "/csv_utils_test_" . uniqid() . ".csv";

        // writeFile should fail if file doesn't exist
        $this->assertFalse(CSV::writeFile($this->tmpFile, [[ "x", "y" ]]));

        // create the file and write
        touch($this->tmpFile);
        $ok = CSV::writeFile($this->tmpFile, [[ "p", "q" ], [ "r", "s" ]]);
        $this->assertTrue($ok);
        $this->assertFileExists($this->tmpFile);

        $content = file_get_contents($this->tmpFile);
        $this->assertStringContainsString("p,q", $content);
        $this->assertStringContainsString("r,s", $content);

        $read = CSV::readFile($this->tmpFile);
        $this->assertIsArray($read);
        $this->assertCount(2, $read);
        $this->assertEquals([ "p", "q" ], $read[0]);

        // invalid file should return empty array
        $this->assertEquals([], CSV::readFile("nonexistent_file.csv"));
    }

    public function testReadFileSkipHeader() {
        $this->tmpFile = sys_get_temp_dir() . "/csv_utils_test_" . uniqid() . ".csv";
        $data = "h1,h2\nv1,v2\nv3,v4\n";
        file_put_contents($this->tmpFile, $data);

        $res = CSV::readFile($this->tmpFile, ",", true);
        $this->assertIsArray($res);
        $this->assertCount(2, $res);
        $this->assertEquals([ "v1", "v2" ], $res[0]);
    }
}
