<?php
namespace Tests\Utils;

use Framework\Utils\JSON;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use stdClass;

class JSONTest extends TestCase {

    private string $tmpFile = "";

    protected function tearDown(): void {
        if ($this->tmpFile !== "" && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }

    public function testIsValid() {
        $this->assertTrue(JSON::isValid('{"a":1}'));
        $this->assertTrue(JSON::isValid('["x","y"]'));

        // invalid JSON strings
        $this->assertFalse(JSON::isValid("not json"));
        $this->assertFalse(JSON::isValid(""));
        $this->assertFalse(JSON::isValid(123));
        $this->assertFalse(JSON::isValid(null));

        // numeric strings are not considered JSON by isValid
        $this->assertFalse(JSON::isValid("123"));
    }

    public function testEncodeDecode() {
        // special cases
        $this->assertEquals("", JSON::encode(null));
        $this->assertEquals('{"a":1}', JSON::isValid('{"a":1}'));
        $this->assertEquals("", JSON::encode(""));
        $this->assertEquals("{}", JSON::encode(new stdClass()));
        $this->assertEquals("123", JSON::encode("123"));

        // encode a normal array
        $arr = [ "x" => "y", "n" => 2 ];
        $encoded = JSON::encode($arr);
        $this->assertIsString($encoded);

        // decode it back
        $decoded = JSON::decode($encoded);
        $this->assertIsArray($decoded);
        $this->assertEquals("y", $decoded["x"]);
        $this->assertEquals(2, $decoded["n"]);
    }

    public function testDecodeAsArray() {
        $json = '{"a":"b","c":3}';
        $arr = JSON::decodeAsArray($json);
        $this->assertIsArray($arr);
        $this->assertEquals("b", $arr["a"]);

        // invalid JSON should return empty array
        $this->assertEquals([], JSON::decodeAsArray("not json"));
        $this->assertEquals([], JSON::decodeAsArray(123));
        $this->assertEquals([], JSON::decodeAsArray(null));

        // already an array should be returned as is
        $inputArr = [ "x" => "y" ];
        $this->assertSame($inputArr, JSON::decodeAsArray($inputArr));
    }

    public function testDecodeAsDictionary() {
        $json = '{"a":"b","c":3}';
        $dict = JSON::decodeAsDictionary($json);
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertEquals("b", $dict->getString("a"));
        $this->assertEquals(3, $dict->getInt("c"));
    }

    public function testDecodeAsStrings() {
        $json = '["one", "", "two"]';
        $strings = JSON::decodeAsStrings($json);
        $this->assertContains("one", $strings);
        $stringsNoEmpty = JSON::decodeAsStrings($json, true);
        $this->assertNotContains("", $stringsNoEmpty);
        $this->assertEquals([ "one", "two" ], array_values($stringsNoEmpty));

        // numeric JSON array becomes strings
        $numJson = "[1,2]";
        $numStrings = JSON::decodeAsStrings($numJson);
        $this->assertEquals([ "1", "2" ], $numStrings);

        // already an array input
        $arrInput = [ 'a', '', 'b' ];
        $arrStrings = JSON::decodeAsStrings($arrInput);
        $this->assertEquals([ "a", "", "b" ], $arrStrings);
        $arrStringsNoEmpty = JSON::decodeAsStrings($arrInput, true);
        $this->assertEquals([ "a", "b" ], $arrStringsNoEmpty);

        // empty or non-json input returns empty list
        $this->assertEquals([], JSON::decodeAsStrings("", true));
        $this->assertEquals([], JSON::decodeAsStrings("plain string"));
    }

    public function testFromCSV() {
        $csv = "a,b,c";
        $csvJson = JSON::fromCSV($csv);
        $this->assertStringContainsString('"a"', $csvJson);
        $this->assertStringContainsString('"b"', $csvJson);
        $this->assertStringContainsString('"c"', $csvJson);

        // test with spaces and empty values
        $csv = " a, b, ,c ";
        $csvJson = JSON::fromCSV($csv);
        $this->assertStringContainsString('"a"', $csvJson);
        $this->assertStringContainsString('"b"', $csvJson);
        $this->assertStringContainsString('"c"', $csvJson);
    }

    public function testReadFileAndWriteFile() {
        $this->tmpFile = sys_get_temp_dir() . "/json_utils_test_" . uniqid() . ".json";

        // writeFile should create the file with pretty JSON
        $ok = JSON::writeFile($this->tmpFile, [ "p" => "q", "num" => 5 ]);
        $this->assertTrue($ok);
        $this->assertFileExists($this->tmpFile);

        // readFile should return array
        $read = JSON::readFile($this->tmpFile);
        $this->assertIsArray($read);
        $this->assertEquals("q", $read["p"]);
    }

    public function testReadUrl() {
        // readUrl using data:// scheme
        $data = json_encode([ "u" => "v" ]);
        $url = "data://text/plain," . rawurlencode($data);
        $fromUrl = JSON::readUrl($url);
        $this->assertIsArray($fromUrl);
        $this->assertEquals("v", $fromUrl["u"]);
    }

    public function testPostUrl() {
        // Use a synthetic test URL that the shim recognizes
        $url = "test://post/endpoint";
        $data = [ "a" => "b", "n" => 3 ];

        // Call the real method; within this namespace our shim intercepts the file_get_contents call
        $res = JSON::postUrl($url, $data);

        $this->assertIsArray($res);
        $this->assertArrayHasKey("ok", $res);
        $this->assertTrue($res["ok"]);

        // Verify shim captured the request options and content
        $this->assertSame($url, $GLOBALS["test_post_url"]);
        $opts = $GLOBALS["test_post_options"];
        $this->assertArrayHasKey("http", $opts);
        $this->assertArrayHasKey("content", $opts["http"]);

        // posted body should be urlencoded form data
        $this->assertStringContainsString("a=b", $opts["http"]["content"]);
        $this->assertStringContainsString("n=3", $opts["http"]["content"]);
    }
}
