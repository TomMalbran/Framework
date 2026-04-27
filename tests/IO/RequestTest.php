<?php
namespace Tests\IO;

use Framework\IO\Request;
use Framework\Date\Type\DateType;
use Framework\Date\Type\DateFormat;
use Framework\Utils\Dictionary;
use Framework\Utils\JSON;

use PHPUnit\Framework\TestCase;

use CURLFile;

class RequestTest extends TestCase {

    protected string $tmpFileF = "";
    protected string $tmpFileG = "";
    protected string $tmpFileH = "";


    protected function setUp(): void {
        $tmpDir = sys_get_temp_dir();
        $this->tmpFileF = $tmpDir . DIRECTORY_SEPARATOR . "req_test_f_" . uniqid();
        $this->tmpFileG = $tmpDir . DIRECTORY_SEPARATOR . "req_test_g_" . uniqid() . ".png";
        $this->tmpFileH = $tmpDir . DIRECTORY_SEPARATOR . "req_test_h_" . uniqid();

        // create a simple text file for f
        @file_put_contents($this->tmpFileF, "hello");

        // create a minimal 1x1 PNG for g
        $pngData = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/w8AAn8B9o7s3wAAAABJRU5ErkJggg==");
        @file_put_contents($this->tmpFileG, $pngData);

        // create an empty file for h (size/error simulated)
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
        // remove temp files we created
        if ($this->tmpFileF !== "" && file_exists($this->tmpFileF)) {
            @unlink($this->tmpFileF);
        }
        if ($this->tmpFileG !== "" && file_exists($this->tmpFileG)) {
            @unlink($this->tmpFileG);
        }
        if ($this->tmpFileH !== "" && file_exists($this->tmpFileH)) {
            @unlink($this->tmpFileH);
        }

        // clear any globals we modified
        $_REQUEST = [];
        $_FILES = [];
    }


    public function testConstruct(): void {
        // test with $_REQUEST
        $_REQUEST = [
            "a" => 1,
            "b" => "x",
        ];
        $r = new Request(withRequest: true);

        $this->assertSame(1, $r->get("a"));
        $this->assertSame("x", $r->get("b"));
    }

    public function testGet(): void {
        $r = new Request([
            "a" => 1,
            "b" => "x",
            "c" => "",
        ]);

        $this->assertSame(1, $r->get("a"));
        $this->assertSame("x", $r->get("b"));
        $this->assertSame("", $r->get("c"));

        $this->assertSame("def", $r->get("missing", "def"));
        $this->assertSame(null, $r->get("missing", null));
    }

    public function testGetOr(): void {
        $r = new Request([
            "a" => 1,
            "b" => "x",
            "c" => "",
        ]);

        $this->assertSame(1, $r->getOr("a", 2));
        $this->assertSame("x", $r->getOr("b", "y"));
        $this->assertSame("z", $r->getOr("c", "z"));

        $this->assertSame("def", $r->getOr("missing", "def"));
        $this->assertSame(5, $r->getOr("missing", 5));
    }

    public function testGetInt(): void {
        $r = new Request([
            "a" => 10,
            "b" => "10",
            "c" => "x",
            "d" => "",
        ]);

        $this->assertSame(10, $r->getInt("a"));
        $this->assertSame(10, $r->getInt("b"));
        $this->assertSame(0, $r->getInt("c"));
        $this->assertSame(0, $r->getInt("d"));

        $this->assertSame(0, $r->getInt("missing"));
        $this->assertSame(5, $r->getInt("missing", 5));
    }

    public function testGetFloat(): void {
        $r = new Request([
            "a" => "1.5",
            "b" => 2.5,
            "c" => "2",
            "d" => "x",
        ]);

        $this->assertEquals(1.5, $r->getFloat("a"));
        $this->assertEquals(2.5, $r->getFloat("b"));
        $this->assertEquals(2.0, $r->getFloat("c"));

        $this->assertSame(0.0, $r->getFloat("d"));
        $this->assertEquals(0.0, $r->getFloat("missing"));
        $this->assertEquals(3.5, $r->getFloat("missing", 3.5));
    }

    public function testGetString(): void {
        $r = new Request([
            "a" => "  hello  ",
            "b" => 123,
            "c" => "",
        ]);

        $this->assertSame("hello", $r->getString("a"));
        $this->assertSame("123", $r->getString("b"));
        $this->assertSame("", $r->getString("c"));

        $this->assertSame("", $r->getString("missing"));
        $this->assertSame("default", $r->getString("missing", "default"));
    }

    public function testGetArray(): void {
        $r = new Request([
            "a" => [ "", "a", null, 1 ],
            "b" => "x",
            "c" => [],
        ]);

        $this->assertSame([ "a", 1 ], $r->getArray("a"));
        $this->assertSame([], $r->getArray("b"));
        $this->assertSame([], $r->getArray("c"));
        $this->assertSame([], $r->getArray("missing"));
    }

    public function testGetDictionary(): void {
        $r = new Request([
            "a" => JSON::encode([ "x" => 1 ]),
            "b" => "x",
        ]);

        $this->assertSame(1, $r->getDictionary("a")->getInt("x"));
        $this->assertTrue($r->getDictionary("b")->isEmpty());
        $this->assertTrue($r->getDictionary("missing")->isEmpty());
    }

    public function testGetJSONArray(): void {
        $r = new Request([
            "a" => JSON::encode([ 1, 2, 3 ]),
            "b" => "x",
            "c" => JSON::encode([]),
        ]);

        $this->assertSame([ 1, 2, 3 ], $r->getJSONArray("a"));
        $this->assertSame([], $r->getJSONArray("b"));
        $this->assertSame([], $r->getJSONArray("c"));
        $this->assertSame([], $r->getJSONArray("missing"));
    }

    public function testGetInts(): void {
        $r = new Request([
            "a" => JSON::encode([ "4", "5" ]),
            "b" => "1,2,3",
            "c" => [ "6", "x", 7 ],
            "d" => [ 1, 0, "2", "0" ],
            "e" => "x",
            "f" => "",
        ]);

        $this->assertSame([ 4, 5 ], $r->getInts("a"));
        $this->assertSame([ 1, 2, 3 ], $r->getInts("b"));
        $this->assertSame([ 6, 7 ], $r->getInts("c"));
        $this->assertSame([ 1, 0, 2, 0 ], $r->getInts("d", withoutEmpty: false));

        $this->assertSame([], $r->getInts("e"));
        $this->assertSame([], $r->getInts("f"));
        $this->assertSame([], $r->getInts("missing"));
    }

    public function testGetStrings(): void {
        $r = new Request([
            "a" => JSON::encode([ "x", "y" ]),
            "b" => "a,b,c",
            "c" => [ "x", null, "y", "" ],
            "d" => "x",
            "e" => "",
        ]);

        $this->assertSame([ "x", "y" ], $r->getStrings("a"));
        $this->assertSame([ "a", "b", "c" ], $r->getStrings("b"));
        $this->assertSame([ "x", "y" ], $r->getStrings("c"));
        $this->assertSame([ "x" ], $r->getStrings("d"));

        $this->assertSame([], $r->getStrings("e"));
        $this->assertSame([], $r->getStrings("missing"));
    }

    public function testGetStringsMap(): void {
        $r = new Request([
            "a" => JSON::encode([ "a" => "v" ]),
            "b" => [ "a" => 1, "b" => "x" ],
            "c" => "x",
            "d" => JSON::encode([]),
        ]);

        $this->assertSame([ "a" => "v" ], $r->getStringsMap("a"));
        $this->assertSame([ "a" => "1", "b" => "x" ], $r->getStringsMap("b"));

        $this->assertSame([], $r->getStringsMap("c"));
        $this->assertSame([], $r->getStringsMap("d"));
        $this->assertSame([], $r->getStringsMap("missing"));
    }

    public function testGetStringIntMap(): void {
        $r = new Request([
            "a" => JSON::encode([ "a" => 2 ]),
            "b" => [ "a" => "2", "b" => "x" ],
            "c" => "x",
            "d" => JSON::encode([]),
        ]);

        $this->assertSame([ "a" => 2 ], $r->getStringIntMap("a"));
        $this->assertSame([ "a" => 2, "b" => 0 ], $r->getStringIntMap("b"));

        $this->assertSame([], $r->getStringIntMap("c"));
        $this->assertSame([], $r->getStringIntMap("d"));
        $this->assertSame([], $r->getStringIntMap("missing"));
    }

    public function testSet(): void {
        $r = new Request([]);

        $r->set("x", 1);
        $this->assertSame(1, $r->get("x"));

        $r->set("x", "2");
        $this->assertSame("2", $r->get("x"));

        $r->set("arr", [ "", "b", null, 3 ]);
        $this->assertSame([ "b", 3 ], $r->getArray("arr"));

        $r->set("n", null);
        $this->assertFalse($r->exists("n"));
        $this->assertFalse($r->has("n"));
        $this->assertSame("", $r->get("n"));
    }

    public function testRemove(): void {
        $r = new Request([
            "a" => 1,
            "b" => "x",
        ]);

        $r->remove("a");
        $this->assertFalse($r->exists("a"));
        $this->assertSame("def", $r->get("a", "def"));

        // removing missing keys is a no-op
        $r->remove("missing");
        $this->assertFalse($r->exists("missing"));
    }

    public function testHas(): void {
        $r = new Request([
            "a" => "",
            "b" => "1",
            "c" => [ "z" ],
            "d" => null,
            "e" => 0,
        ]);

        // non-empty request overall
        $this->assertTrue($r->has());

        // single key checks
        $this->assertFalse($r->has("a"));
        $this->assertTrue($r->has("b"));
        $this->assertTrue($r->has("c"));
        $this->assertFalse($r->has("d"));
        $this->assertFalse($r->has("missing"));

        // array of keys: all must be non-empty
        $this->assertTrue($r->has([ "b", "c" ]));
        $this->assertFalse($r->has([ "a", "b" ]));

        // index param: checks array element presence/non-empty
        $this->assertTrue($r->has("c", 0));
        $this->assertFalse($r->has("c", 1));

        // null key checks (returns whether request has any data)
        $this->assertTrue($r->has(null));
        $empty = new Request([]);
        $this->assertFalse($empty->has(null));
    }

    public function testExists(): void {
        $r = new Request([
            "a" => "",
            "b" => "1",
            "c" => null,
            "d" => 0,
        ]);

        // key present even if empty string
        $this->assertTrue($r->exists("a"));

        // null value is not considered present
        $this->assertFalse($r->exists("c"));

        // zero is present
        $this->assertTrue($r->exists("d"));

        // missing key is not present
        $this->assertFalse($r->exists("missing"));

        // should work with array of keys: all must exist
        $this->assertTrue($r->exists([ "a", "b", "d" ]));
        $this->assertFalse($r->exists([ "a", "c" ]));
    }

    public function testIsActive(): void {
        $r = new Request([
            "a" => "1",
            "b" => true,
            "c" => "true",
            "d" => 1,
            "e" => "",
            "f" => null,
            "g" => 0,
        ]);

        // active means non-empty / truthy for our API
        $this->assertTrue($r->isActive("a"));
        $this->assertTrue($r->isActive("b"));
        $this->assertTrue($r->isActive("c"));
        $this->assertTrue($r->isActive("d"));

        // empty string is not active
        $this->assertFalse($r->isActive("e"));

        // null is not active
        $this->assertFalse($r->isActive("f"));

        // numeric zero is not active
        $this->assertFalse($r->isActive("g"));

        // missing is not active
        $this->assertFalse($r->isActive("missing"));
    }

    public function testIsEmpty(): void {
        $r = new Request([
            "a" => "1",
            "b" => "2",
            "c" => "",
            "d" => [],
            "e" => null,
        ]);

        $this->assertFalse($r->isEmpty([ "a" ], [ "b" ]));
        $this->assertTrue($r->isEmpty([ "missing" ]));
        $this->assertTrue($r->isEmpty([ "c" ]));
        $this->assertTrue($r->isEmpty([ "a" ], [ "missing" ]));

        $this->assertTrue($r->isEmpty([ "c" ]));
        $this->assertTrue($r->isEmpty([ "d" ]));
        $this->assertTrue($r->isEmpty([ "e" ]));
    }

    public function testIsEmptyArray(): void {
        $r = new Request([
            "a" => [],
            "b" => [ 1 ],
            "c" => "x",
        ]);

        $this->assertTrue($r->isEmptyArray("a"));
        $this->assertFalse($r->isEmptyArray("b"));
        $this->assertTrue($r->isEmptyArray("c"));
        $this->assertTrue($r->isEmptyArray("missing"));
    }

    public function testIsValidString(): void {
        $r = new Request([
            "a" => "ok",
            "b" => "",
            "c" => null,
            "d" => "   ",
            "e" => "  x  ",
        ]);

        $this->assertTrue($r->isValidString("a"));
        $this->assertFalse($r->isValidString("b"));
        $this->assertFalse($r->isValidString("c"));
        $this->assertFalse($r->isValidString("d"));
        $this->assertTrue($r->isValidString("e"));
        $this->assertFalse($r->isValidString("missing"));
    }

    public function testIsValidLength(): void {
        $r = new Request([
            "a" => "abcd",
            "b" => "abcde",
            "c" => "  abc  ",
        ]);

        $this->assertTrue($r->isValidLength("a", 4));
        $this->assertFalse($r->isValidLength("b", 4));
        $this->assertTrue($r->isValidLength("c", 4));
        $this->assertTrue($r->isValidLength("missing", 4));
    }

    public function testIsValidNumber(): void {
        $r = new Request([
            "a" => "12",
            "b" => "12.5",
            "c" => 3,
            "d" => 3.5,
            "e" => "x",
            "z" => "0",
        ]);

        $this->assertTrue($r->isValidNumber("a"));
        $this->assertTrue($r->isValidNumber("b"));
        $this->assertTrue($r->isValidNumber("c"));
        $this->assertTrue($r->isValidNumber("d"));
        $this->assertFalse($r->isValidNumber("e"));
        $this->assertTrue($r->isValidNumber("z"));
        $this->assertFalse($r->isValidNumber("missing"));
    }

    public function testIsNumeric(): void {
        $r = new Request([
            "a" => "5",
            "b" => "00",
            "c" => "-3",
            "d" => "x",
            "e" => "1.5",
        ]);

        // basic numeric checks
        $this->assertTrue($r->isNumeric("a", 1));
        $this->assertFalse($r->isNumeric("a", 7));
        $this->assertFalse($r->isNumeric("b"));
        $this->assertTrue($r->isNumeric("b", 0));
        $this->assertFalse($r->isNumeric("c"));
        $this->assertTrue($r->isNumeric("c", null));
        $this->assertFalse($r->isNumeric("d"));

        // floats are considered numeric
        $this->assertTrue($r->isNumeric("e"));
        $this->assertFalse($r->isNumeric("missing"));

        // min/max length checks (add a longer value)
        $r->set("f", "123");
        $this->assertTrue($r->isNumeric("f", 1, 200));
        $this->assertFalse($r->isNumeric("f", 1, 2));

        // decimals parameter: allowed decimal places
        // 'e' = "1.5" -> 1 decimal
        $this->assertTrue($r->isNumeric("e", null, null, 1));
        $this->assertFalse($r->isNumeric("e", null, null, 0));

        // negative numbers with zero decimals
        $this->assertTrue($r->isNumeric("c", null, null, 0));
    }

    public function testIsValidPrice(): void {
        $r = new Request([
            "a" => "10.50",
            "b" => "10",
            "c" => "x",
            "d" => "",
        ]);

        $this->assertTrue($r->isValidPrice("a"));
        $this->assertTrue($r->isValidPrice("b"));
        $this->assertFalse($r->isValidPrice("c"));
        $this->assertFalse($r->isValidPrice("d"));
        $this->assertFalse($r->isValidPrice("missing"));

        // min parameter: default min is 1 (prices below 1 are invalid)
        $r->set("small", "0.50");
        $this->assertFalse($r->isValidPrice("small"));
        $this->assertTrue($r->isValidPrice("small", null)); // disable min check
        $this->assertTrue($r->isValidPrice("small", 0));

        // max parameter: enforce an upper bound
        $r->set("big", "200");
        $this->assertTrue($r->isValidPrice("big", 1, 500));
        $this->assertFalse($r->isValidPrice("big", 1, 100));

        // price must have at most 2 decimals (isValidPrice enforces 2 decimals)
        $r->set("too_many_decimals", "1.234");
        $this->assertFalse($r->isValidPrice("too_many_decimals"));

        // negative prices are invalid with default min
        $r->set("neg", "-1.00");
        $this->assertFalse($r->isValidPrice("neg"));
    }

    public function testIsAlphaNum(): void {
        $r = new Request([
            "a" => "abc-123",
            "b" => "abc123",
            "c" => "abc_123",
            "d" => "",
            "e" => null,
        ]);

        // allow dash/hyphen when second param is true
        $this->assertTrue($r->isAlphaNum("a", true));
        $this->assertFalse($r->isAlphaNum("a", false));

        // plain alphanumeric
        $this->assertTrue($r->isAlphaNum("b", false));

        // underscore are valid
        $this->assertTrue($r->isAlphaNum("c", true));

        // length parameter: must match exact length of the original string
        $this->assertTrue($r->isAlphaNum("a", true, 7));
        $this->assertFalse($r->isAlphaNum("a", true, 6));
        $this->assertTrue($r->isAlphaNum("b", false, 6));
        $this->assertTrue($r->isAlphaNum("c", true, 7));
        $this->assertFalse($r->isAlphaNum("c", true, 8));

        // empty/null/missing are invalid
        $this->assertFalse($r->isAlphaNum("d", true));
        $this->assertFalse($r->isAlphaNum("e", true));
        $this->assertFalse($r->isAlphaNum("missing", true));
    }

    public function testIsValidEmail(): void {
        $r = new Request([
            "a" => "test@example.com",
            "b" => "invalid-email",
            "c" => "",
            "d" => null,
        ]);

        $this->assertTrue($r->isValidEmail("a"));
        $this->assertFalse($r->isValidEmail("b"));
        $this->assertFalse($r->isValidEmail("c"));
        $this->assertFalse($r->isValidEmail("d"));
        $this->assertFalse($r->isValidEmail("missing"));
    }

    public function testIsValidPassword(): void {
        $r = new Request([
            "a" => "abc123",
            "b" => "aBc123",
            "c" => "abcdef",
            "d" => "a1b2c3",
            "e" => "ab12",
            "f" => "",
        ]);

        $this->assertTrue($r->isValidPassword("a"));
        $this->assertTrue($r->isValidPassword("b", "lud"));
        $this->assertTrue($r->isValidPassword("b", "ud", 5));

        $this->assertFalse($r->isValidPassword("c"));
        $this->assertFalse($r->isValidPassword("d", "lud"));
        $this->assertFalse($r->isValidPassword("e", "lud", 5));
        $this->assertFalse($r->isValidPassword("f"));
        $this->assertFalse($r->isValidPassword("missing"));
    }

    public function testIsValidUsername(): void {
        $r = new Request([
            "a" => "user1",
            "b" => "2user",
            "c" => "user-3",
            "d" => "423",
            "e" => "user_5",
            "f" => "-user6",
            "g" => "user7-",
            "h" => "",
        ]);

        $this->assertTrue($r->isValidUsername("a"));
        $this->assertTrue($r->isValidUsername("b"));
        $this->assertTrue($r->isValidUsername("c"));
        $this->assertTrue($r->isValidUsername("d"));

        $this->assertFalse($r->isValidUsername("e"));
        $this->assertFalse($r->isValidUsername("f"));
        $this->assertFalse($r->isValidUsername("g"));
        $this->assertFalse($r->isValidUsername("h"));
        $this->assertFalse($r->isValidUsername("missing"));
    }

    public function testIsValidColor(): void {
        $r = new Request([
            "a" => "#fff",
            "b" => "#ffffff",
            "c" => "fff",
            "d" => "rgb(255,0,0)",
            "e" => "rgba(255,0,0,0.5)",
            "f" => "hsl(120,100%,50%)",
            "g" => "not-a-color",
            "h" => "",
            "i" => null,
        ]);

        $this->assertTrue($r->isValidColor("a"));
        $this->assertTrue($r->isValidColor("b"));

        $this->assertFalse($r->isValidColor("c"));
        $this->assertFalse($r->isValidColor("d"));
        $this->assertFalse($r->isValidColor("e"));
        $this->assertFalse($r->isValidColor("f"));
        $this->assertFalse($r->isValidColor("g"));
        $this->assertFalse($r->isValidColor("h"));
        $this->assertFalse($r->isValidColor("i"));
        $this->assertFalse($r->isValidColor("missing"));
    }

    public function testIsValidCUIT(): void {
        $r = new Request([
            "a" => "20-12345678-6",
            "b" => "20123456786",
            "c" => "20 12345678 6",
            "d" => "20-12345678-0",
            "e" => "123",
            "f" => "",
            "g" => null,
        ]);

        $this->assertTrue($r->isValidCUIT("a"));
        $this->assertTrue($r->isValidCUIT("b"));
        $this->assertTrue($r->isValidCUIT("c"));

        $this->assertFalse($r->isValidCUIT("d"));
        $this->assertFalse($r->isValidCUIT("e"));
        $this->assertFalse($r->isValidCUIT("f"));
        $this->assertFalse($r->isValidCUIT("g"));
        $this->assertFalse($r->isValidCUIT("missing"));
    }

    public function testIsValidDNI(): void {
        $r = new Request([
            "a" => "12.345.678",
            "b" => "12345678",
            "c" => "12 345 678",
            "d" => "123",
            "e" => "abc",
            "f" => "",
            "g" => null,
        ]);

        $this->assertTrue($r->isValidDNI("a"));
        $this->assertTrue($r->isValidDNI("b"));
        $this->assertTrue($r->isValidDNI("c"));

        $this->assertFalse($r->isValidDNI("d"));
        $this->assertFalse($r->isValidDNI("e"));
        $this->assertFalse($r->isValidDNI("f"));
        $this->assertFalse($r->isValidDNI("g"));
        $this->assertFalse($r->isValidDNI("missing"));

        // numeric extraction
        $this->assertMatchesRegularExpression('/^12345678$/', $r->dniToNumber("a"));
        $this->assertSame("12345678", $r->dniToNumber("b"));
        $this->assertSame("12345678", $r->dniToNumber("c"));
    }

    public function testIsValidPhone(): void {
        $r = new Request([
            "a" => "(123) 456-7890",
            "b" => "123-456-7890",
            "c" => "+1 123 456 7890",
            "d" => "1234567",
            "e" => "abc",
            "f" => "",
            "g" => null,
        ]);

        $this->assertTrue($r->isValidPhone("a"));
        $this->assertTrue($r->isValidPhone("b"));
        $this->assertTrue($r->isValidPhone("c"));
        $this->assertTrue($r->isValidPhone("d"));

        $this->assertFalse($r->isValidPhone("e"));
        $this->assertFalse($r->isValidPhone("f"));
        $this->assertFalse($r->isValidPhone("g"));
        $this->assertFalse($r->isValidPhone("missing"));

        // numeric extraction
        $this->assertMatchesRegularExpression('/^1234567890$/', $r->phoneToNumber("a"));
        $this->assertSame("1234567890", $r->phoneToNumber("b"));
        $this->assertMatchesRegularExpression('/^1?1234567890$/', $r->phoneToNumber("c"));
    }

    public function testIsValidUrl(): void {
        $r = new Request([
            "a" => "https://example.com/path",
            "b" => "http://sub.example.com",
            "c" => "ftp://example.com",
            "d" => "example.com",
            "e" => "https://example.com:8080/path",
            "f" => "not-a-url",
            "g" => "",
            "h" => null,
        ]);

        // only http/https schemes are considered valid by URL::isValid
        $this->assertTrue($r->isValidUrl("a"));
        $this->assertTrue($r->isValidUrl("b"));
        $this->assertFalse($r->isValidUrl("c")); // ftp is not accepted
        $this->assertFalse($r->isValidUrl("d"));
        $this->assertTrue($r->isValidUrl("e"));
        $this->assertFalse($r->isValidUrl("f"));
        $this->assertFalse($r->isValidUrl("g"));
        $this->assertFalse($r->isValidUrl("h"));
        $this->assertFalse($r->isValidUrl("missing"));
    }

    public function testIsValidDomain(): void {
        $r = new Request([
            "a" => "https://example.com/path",
            "b" => "http://sub.example.com",
            "c" => "www.example.com",
            "d" => "subdomain.example.co.uk",
            "e" => "not-a-domain",
            "f" => "",
            "g" => null,
        ]);

        $this->assertTrue($r->isValidDomain("a"));
        $this->assertTrue($r->isValidDomain("b"));
        $this->assertTrue($r->isValidDomain("c"));
        $this->assertTrue($r->isValidDomain("d"));

        $this->assertFalse($r->isValidDomain("e"));
        $this->assertFalse($r->isValidDomain("f"));
        $this->assertFalse($r->isValidDomain("g"));
        $this->assertFalse($r->isValidDomain("missing"));
    }

    public function testIsValidSlug(): void {
        $r = new Request([
            "a" => "valid-slug",
            "b" => "valid-slug-123",
            "c" => "123",
            "d" => "invalid slug",
            "e" => "invalid_slug",
            "f" => "Invalid-Slug",
            "g" => "",
            "h" => null,
        ]);

        $this->assertTrue($r->isValidSlug("a"));
        $this->assertTrue($r->isValidSlug("b"));
        $this->assertTrue($r->isValidSlug("c"));

        $this->assertFalse($r->isValidSlug("d"));
        $this->assertFalse($r->isValidSlug("e"));
        $this->assertFalse($r->isValidSlug("f"));
        $this->assertFalse($r->isValidSlug("g"));
        $this->assertFalse($r->isValidSlug("h"));
        $this->assertFalse($r->isValidSlug("missing"));
    }

    public function testIsValidPosition(): void {
        $r = new Request([
            "a" => "0",
            "b" => "1",
            "c" => "-1",
            "d" => "xh",
            "e" => "",
            "f" => null,
        ]);

        $this->assertTrue($r->isValidPosition("a"));
        $this->assertTrue($r->isValidPosition("b"));
        $this->assertFalse($r->isValidPosition("c"));
        $this->assertTrue($r->isValidPosition("d"));
        $this->assertTrue($r->isValidPosition("e"));
        $this->assertTrue($r->isValidPosition("f"));
        $this->assertTrue($r->isValidPosition("missing"));
    }

    public function testIsValidDate(): void {
        $r = new Request([
            "a" => "2020-01-01",
            "b" => "2020-02-29",
            "c" => "2021-02-29",
            "d" => "01-01-2020",
            "e" => "",
            "f" => null,
        ]);

        $this->assertTrue($r->isValidDate("a"));
        $this->assertTrue($r->isValidDate("b"));
        $this->assertTrue($r->isValidDate("c"));
        $this->assertTrue($r->isValidDate("d"));

        $this->assertFalse($r->isValidDate("e"));
        $this->assertFalse($r->isValidDate("f"));
        $this->assertFalse($r->isValidDate("missing"));
    }

    public function testIsValidHour(): void {
        $r = new Request([
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
        ]);

        $this->assertTrue($r->isValidHour("a"));
        $this->assertTrue($r->isValidHour("b"));
        $this->assertTrue($r->isValidHour("c"));

        $this->assertFalse($r->isValidHour("d"));
        $this->assertFalse($r->isValidHour("e"));
        $this->assertFalse($r->isValidHour("f"));
        $this->assertFalse($r->isValidHour("g"));
        $this->assertFalse($r->isValidHour("missing"));

        // minutes restriction: only allow minutes 0 or 30
        $this->assertTrue($r->isValidHour("h", [ 0, 30 ]));
        $this->assertFalse($r->isValidHour("i", [ 0, 30 ]));

        // minHour restriction: allow hours >= 5
        $this->assertTrue($r->isValidHour("j", null, 5));
        $this->assertFalse($r->isValidHour("k", null, 5));

        // maxHour restriction: allow hours <= 20
        $this->assertTrue($r->isValidHour("l", null, 0, 20));
        $this->assertFalse($r->isValidHour("m", null, 0, 20));

        // invalid minute values are rejected
        $this->assertFalse($r->isValidHour("n"));
    }

    public function testIsValidPeriod(): void {
        $r = new Request([
            "a" => "2020-01-01",
            "b" => "2020-01-02",
            "c" => "2020-01-02",
            "d" => "2020-01-01",
            "e" => "2021-02-29",
            "f" => "01-01-2020",
        ]);

        $this->assertTrue($r->isValidPeriod("a", "b")); // normal range
        $this->assertTrue($r->isValidPeriod("a", "a")); // same day allowed
        $this->assertTrue($r->isValidPeriod("a", "f")); // valid mixed formats

        $this->assertFalse($r->isValidPeriod("c", "d")); // from > to
        $this->assertFalse($r->isValidPeriod("e", "b")); // invalid from date

        $this->assertFalse($r->isValidPeriod("missing", "b"));
        $this->assertFalse($r->isValidPeriod("a", "missing"));
    }

    public function testIsValidHourPeriod(): void {
        $r = new Request([
            "a" => "08:00", "b" => "10:00",
            "c" => "10:00", "d" => "08:00",
            "e" => "08:00", "f" => "08:00",
            "h" => "24:00", "i" => "25:00",
            "j" => "23:00", "k" => "01:00",
        ]);

        // normal range
        $this->assertTrue($r->isValidHourPeriod("a", "b"));

        // from greater than to
        $this->assertFalse($r->isValidHourPeriod("c", "d"));

        // equal hours are not allowed
        $this->assertFalse($r->isValidHourPeriod("e", "f"));

        // invalid hour formats are rejected
        $this->assertFalse($r->isValidHourPeriod("h", "i"));

        // crossing midnight not considered a valid same-day period
        $this->assertFalse($r->isValidHourPeriod("j", "k"));

        // missing keys return false (method short-circuits to false)
        $this->assertFalse($r->isValidHourPeriod("missing", "b"));
        $this->assertFalse($r->isValidHourPeriod("a", "missing"));
    }

    public function testIsValidFullPeriod(): void {
        $r = new Request([
            // normal range: same day, from < to
            "a_date" => "2020-01-01", "a_hour" => "08:00",
            "b_date" => "2020-01-01", "b_hour" => "10:00",

            // crossing days: from day before to next day
            "c_date" => "2020-01-01", "c_hour" => "23:00",
            "d_date" => "2020-01-02", "d_hour" => "01:00",

            // equal datetime (invalid)
            "e_date" => "2020-01-01", "e_hour" => "09:00",
            "f_date" => "2020-01-01", "f_hour" => "09:00",

            // from after to (invalid)
            "g_date" => "2020-01-02", "g_hour" => "12:00",
            "h_date" => "2020-01-01", "h_hour" => "12:00",

            // invalid formats
            "i_date" => "not-a-date", "i_hour" => "08:00",
            "j_date" => "2020-01-01", "j_hour" => "25:00",
        ]);

        // normal same-day range
        $this->assertTrue($r->isValidFullPeriod("a_date", "a_hour", "b_date", "b_hour"));

        // crossing to next day is valid as long as datetime increases
        $this->assertTrue($r->isValidFullPeriod("c_date", "c_hour", "d_date", "d_hour"));

        // equal datetime is not valid (from must be strictly before to)
        $this->assertFalse($r->isValidFullPeriod("e_date", "e_hour", "f_date", "f_hour"));

        // from date/time after to date/time is invalid
        $this->assertFalse($r->isValidFullPeriod("g_date", "g_hour", "h_date", "h_hour"));

        // invalid date or hour formats are rejected
        $this->assertFalse($r->isValidFullPeriod("i_date", "i_hour", "b_date", "b_hour"));
        $this->assertFalse($r->isValidFullPeriod("a_date", "a_hour", "j_date", "j_hour"));

        // missing keys short-circuit to false per Request implementation
        $this->assertFalse($r->isValidFullPeriod("missing", "a_hour", "b_date", "b_hour"));
        $this->assertFalse($r->isValidFullPeriod("a_date", "missing", "b_date", "b_hour"));
    }

    public function testIsValidWeekDay(): void {
        $r = new Request([
            "a" => 0,
            "b" => 6,
            "c" => -1,
            "d" => 7,
            "e" => "3",
            "f" => null,

            // startMonday cases
            "sm1" => 1,
            "sm7" => 7,
            "sm0" => 0,
        ]);

        // default (startMonday = false): valid range is 0..6
        $this->assertTrue($r->isValidWeekDay("a"));
        $this->assertTrue($r->isValidWeekDay("b"));
        $this->assertFalse($r->isValidWeekDay("c"));
        $this->assertFalse($r->isValidWeekDay("d"));
        $this->assertTrue($r->isValidWeekDay("e"));

        // missing keys default to 0 via getInt() and are valid when startMonday = false
        $this->assertTrue($r->isValidWeekDay("missing"));
        $this->assertTrue($r->isValidWeekDay("f"));

        // startMonday = true: valid range is 1..7
        $this->assertTrue($r->isValidWeekDay("sm1", true));
        $this->assertTrue($r->isValidWeekDay("sm7", true));
        $this->assertFalse($r->isValidWeekDay("sm0", true));
        $this->assertTrue($r->isValidWeekDay("e", true));

        // missing key with startMonday = true becomes 0 and should be invalid
        $this->assertFalse($r->isValidWeekDay("missing", true));
    }

    public function testIsFutureDate(): void {
        $r = new Request([
            "future" => "2099-01-01",
            "past"   => "2000-01-01",
        ]);

        $this->assertTrue($r->isFutureDate("future"));
        $this->assertFalse($r->isFutureDate("past"));

        // Missing key should be considered not future
        $this->assertFalse($r->isFutureDate("missing"));

        // Explicit DateType parameter works as well
        $this->assertTrue($r->isFutureDate("future", DateType::Start));
    }

    public function testToBinary(): void {
        $r = new Request([
            "a" => "1",
            "b" => true,
            "c" => false,
            "d" => "true",
            "e" => "false",
            "f" => "0",
            "g" => "",
        ]);

        // toBinary: truthy values map to the default when present
        $this->assertSame(1, $r->toBinary("a", 1));
        $this->assertSame(1, $r->toBinary("b", 1));
        $this->assertSame(0, $r->toBinary("c", 1));
        $this->assertSame(1, $r->toBinary("d", 1));
        $this->assertSame(0, $r->toBinary("e", 1));
        $this->assertSame(0, $r->toBinary("f", 1));
        $this->assertSame(0, $r->toBinary("g", 1));

        // missing key short-circuits to 0
        $this->assertSame(0, $r->toBinary("missing", 1));
    }

    public function testToInt(): void {
        $r = new Request([
            "a" => "12.34",
            "b" => "12.345",
            "c" => "12.3",
            "d" => "abc",
            "e" => "",
        ]);

        // toInt respects the decimals multiplier (no forced rounding down)
        $this->assertSame(1234, $r->toInt("a", 2));
        $this->assertSame(12345, $r->toInt("b", 3));
        $this->assertSame(1230, $r->toInt("c", 2));

        // invalid numeric values return 0
        $this->assertSame(0, $r->toInt("d", 2));
        $this->assertSame(0, $r->toInt("e", 2));
        $this->assertSame(0, $r->toInt("missing", 2));
    }

    public function testToCents(): void {
        $r = new Request([
            "a" => "12.34",
            "b" => "-1.23",
            "c" => "abc",
            "d" => "",
        ]);

        // toCents multiplies by 100
        $this->assertSame(1234, $r->toCents("a"));
        $this->assertSame(-123, $r->toCents("b"));

        // invalid numeric values return 0
        $this->assertSame(0, $r->toCents("c"));
        $this->assertSame(0, $r->toCents("d"));
        $this->assertSame(0, $r->toCents("missing"));
    }

    public function testToJSON(): void {
        $r = new Request([
            "a" => [ 1, 2 ],
            "b" => "1,2,3",
            "c" => JSON::encode([ 4, 5 ]),
            "d" => "",
            "e" => [],
        ]);

        $this->assertSame(JSON::encode([ 1, 2 ]), $r->toJSON("a"));
        $this->assertSame(JSON::encode([ "1", "2", "3" ]), $r->toJSON("b"));
        $this->assertSame(JSON::encode([ 4, 5 ]), $r->toJSON("c"));

        $this->assertSame(JSON::encode([]), $r->toJSON("d"));
        $this->assertSame(JSON::encode([]), $r->toJSON("e"));
        $this->assertSame(JSON::encode([]), $r->toJSON("missing"));
    }

    public function testCuitToNumber(): void {
        $r = new Request([
            "a" => "20 12345678 6",
            "b" => "20123456786",
            "c" => "20-12345678-6",
            "d" => "invalid-cuit",
            "e" => "",
        ]);

        $this->assertSame("20123456786", $r->cuitToNumber("a"));
        $this->assertSame("20123456786", $r->cuitToNumber("b"));
        $this->assertSame("20123456786", $r->cuitToNumber("c"));

        $this->assertSame("", $r->cuitToNumber("d"));
        $this->assertSame("", $r->cuitToNumber("e"));
        $this->assertSame("", $r->cuitToNumber("missing"));
    }

    public function testDniToNumber(): void {
        $r = new Request([
            "a" => "12.345.678",
            "b" => "12345678",
            "c" => "12 345 678",
            "d" => "invalid-dni",
            "e" => "",
        ]);

        $this->assertSame("12345678", $r->dniToNumber("a"));
        $this->assertSame("12345678", $r->dniToNumber("b"));
        $this->assertSame("12345678", $r->dniToNumber("c"));

        $this->assertSame("", $r->dniToNumber("d"));
        $this->assertSame("", $r->dniToNumber("e"));
        $this->assertSame("", $r->dniToNumber("missing"));
    }

    public function testPhoneToNumber(): void {
        $r = new Request([
            "a" => "(123)456-7890",
            "b" => "123-456-7890",
            "c" => "+1 123 456 7890",
            "d" => "1234567",
            "e" => "invalid-phone",
            "f" => "",
        ]);

        $this->assertSame("1234567890", $r->phoneToNumber("a"));
        $this->assertSame("1234567890", $r->phoneToNumber("b"));
        $this->assertSame("11234567890", $r->phoneToNumber("c"));
        $this->assertSame("1234567", $r->phoneToNumber("d"));

        $this->assertSame("", $r->phoneToNumber("e"));
        $this->assertSame("", $r->phoneToNumber("f"));
        $this->assertSame("", $r->phoneToNumber("missing"));
    }

    public function testToDomain(): void {
        $r = new Request([
            "a" => "https://sub.example.com/path",
            "b" => "http://www.example.com",
            "c" => "https://example.com/path",
            "d" => "not-a-url",
            "e" => "",
        ]);

        $this->assertSame("sub.example.com", $r->toDomain("a"));
        $this->assertSame("example.com", $r->toDomain("b"));
        $this->assertSame("example.com", $r->toDomain("c"));

        $this->assertSame("not-a-url", $r->toDomain("d"));
        $this->assertSame("", $r->toDomain("e"));
        $this->assertSame("", $r->toDomain("missing"));
    }

    public function testToDate(): void {
        $r = new Request([
            "a" => "2020-02-01",
            "b" => "01-02-2020",
            "c" => "invalid-date",
            "d" => "",
        ]);

        $this->assertSame("2020-02-01", $r->toDate("a")->toString(DateFormat::Reverse));
        $this->assertSame("2020-02-01", $r->toDate("b")->toString(DateFormat::Reverse));

        $this->assertTrue($r->toDate("c")->isEmpty());
        $this->assertTrue($r->toDate("d")->isEmpty());
        $this->assertTrue($r->toDate("missing")->isEmpty());
    }

    public function testToTimeHour(): void {
        $r = new Request([
            "a" => "2020-02-01",
            "b" => "01-02-2020",
            "c" => "10:00",
            "d" => "23:59",
            "e" => "00:00",
            "f" => "invalid-time",
        ]);

        $this->assertSame("2020-02-01 10:00", $r->toTimeHour("a", "c")->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-02-01 23:59", $r->toTimeHour("b", "d")->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-02-01 00:00", $r->toTimeHour("a", "e")->toString(DateFormat::ReverseTime));

        $this->assertTrue($r->toTimeHour("f", "c")->isEmpty());
        $this->assertTrue($r->toTimeHour("a", "missing", true, true)->isEmpty());
        $this->assertTrue($r->toTimeHour("missing", "c", true, true)->isEmpty());
    }

    public function testToDayMoment(): void {
        $r = new Request([
            "a" => "2020-01-01",
            "b" => "01-01-2020",
            "c" => "2020-02-29",
            "d" => "not-a-date",
            "e" => "",
        ]);

        // Start / Middle / End moments for a normal date
        $this->assertSame("2020-01-01 00:00", $r->toDayMoment("a", DateType::Start)->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-01-01 12:00", $r->toDayMoment("a", DateType::Middle)->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-01-01 23:59", $r->toDayMoment("a", DateType::End)->toString(DateFormat::ReverseTime));

        // Different input format is accepted
        $this->assertSame("2020-01-01 00:00", $r->toDayMoment("b", DateType::Start)->toString(DateFormat::ReverseTime));

        // Leap day handled correctly
        $this->assertSame("2020-02-29 00:00", $r->toDayMoment("c", DateType::Start)->toString(DateFormat::ReverseTime));

        // invalid or empty inputs return an empty Date
        $this->assertTrue($r->toDayMoment("d", DateType::Start)->isEmpty());
        $this->assertTrue($r->toDayMoment("e", DateType::Start)->isEmpty());
        $this->assertTrue($r->toDayMoment("missing", DateType::Start)->isEmpty());
    }

    public function testToDayStart(): void {
        $r = new Request([
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ]);

        $this->assertSame("2020-03-15 00:00", $r->toDayStart("a")->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-03-15 00:00", $r->toDayStart("b")->toString(DateFormat::ReverseTime));

        $this->assertTrue($r->toDayStart("c")->isEmpty());
        $this->assertTrue($r->toDayStart("d")->isEmpty());
        $this->assertTrue($r->toDayStart("missing")->isEmpty());
    }

    public function testToDayStartHour(): void {
        $r = new Request([
            "a"   => "2020-03-15",
            "a_h" => "09:30",
            "b"   => "15-03-2020",
            "c"   => "",
            "d"   => "not-a-date",
            "e"   => "2020-03-16",
            "e_h" => "invalid-time",
        ]);

        // when hour key is present, the provided hour should be used
        $this->assertSame("2020-03-15 09:30", $r->toDayStartHour("a", "a_h")->toString(DateFormat::ReverseTime));

        // when hour key is missing, fall back to start of day
        $this->assertSame("2020-03-15 00:00", $r->toDayStartHour("b", "missing")->toString(DateFormat::ReverseTime));

        // empty or invalid date inputs return an empty Date
        $this->assertTrue($r->toDayStartHour("c", "a_h")->isEmpty());
        $this->assertTrue($r->toDayStartHour("d", "missing")->isEmpty());

        // invalid hour when present should yield an empty Date (toTimeHour -> empty)
        $this->assertTrue($r->toDayStartHour("e", "e_h")->isEmpty());

        // missing keys produce empty
        $this->assertTrue($r->toDayStartHour("missing", "missing")->isEmpty());
    }

    public function testToDayMiddle(): void {
        $r = new Request([
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ]);

        $this->assertSame("2020-03-15 12:00", $r->toDayMiddle("a")->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-03-15 12:00", $r->toDayMiddle("b")->toString(DateFormat::ReverseTime));

        $this->assertTrue($r->toDayMiddle("c")->isEmpty());
        $this->assertTrue($r->toDayMiddle("d")->isEmpty());
        $this->assertTrue($r->toDayMiddle("missing")->isEmpty());
    }

    public function testToDayEnd(): void {
        $r = new Request([
            "a" => "2020-03-15",
            "b" => "15-03-2020",
            "c" => "",
            "d" => "not-a-date",
        ]);

        $this->assertSame("2020-03-15 23:59", $r->toDayEnd("a")->toString(DateFormat::ReverseTime));
        $this->assertSame("2020-03-15 23:59", $r->toDayEnd("b")->toString(DateFormat::ReverseTime));

        $this->assertTrue($r->toDayEnd("c")->isEmpty());
        $this->assertTrue($r->toDayEnd("d")->isEmpty());
        $this->assertTrue($r->toDayEnd("missing")->isEmpty());
    }

    public function testToDayEndHour(): void {
        $r = new Request([
            "a"   => "2020-03-15",
            "a_h" => "18:45",
            "b"   => "15-03-2020",
            "c"   => "",
            "d"   => "not-a-date",
            "e"   => "2020-03-16",
            "e_h" => "invalid-time",
        ]);

        // when hour key is present, the provided hour should be used
        $this->assertSame("2020-03-15 18:45", $r->toDayEndHour("a", "a_h")->toString(DateFormat::ReverseTime));

        // when hour key is missing, fall back to end of day
        $this->assertSame("2020-03-15 23:59", $r->toDayEndHour("b", "missing")->toString(DateFormat::ReverseTime));

        // empty or invalid date inputs return an empty Date
        $this->assertTrue($r->toDayEndHour("c", "a_h")->isEmpty());
        $this->assertTrue($r->toDayEndHour("d", "missing")->isEmpty());

        // invalid hour when present should yield an empty Date (toTimeHour -> empty)
        $this->assertTrue($r->toDayEndHour("e", "e_h")->isEmpty());

        // missing keys produce empty
        $this->assertTrue($r->toDayEndHour("missing", "missing")->isEmpty());
    }

    public function testGetFile(): void {
        $r = new Request(withFiles: true);

        $this->assertSame($_FILES["f"], $r->getFile("f"));
        $this->assertSame($_FILES["g"], $r->getFile("g"));
        $this->assertNull($r->getFile("missing"));
    }

    public function testGetFileName(): void {
        $r = new Request(withFiles: true);

        $this->assertSame("a.txt", $r->getFileName("f"));
        $this->assertSame("image.PNG", $r->getFileName("g"));
        $this->assertSame("", $r->getFileName("missing"));
    }

    public function testGetFileType(): void {
        $r = new Request(withFiles: true);

        $this->assertSame("text/plain", $r->getFileType("f"));
        $this->assertSame("image/png", $r->getFileType("g"));
        $this->assertSame("", $r->getFileType("missing"));
    }

    public function testGetTmpName(): void {
        $r = new Request(withFiles: true);

        $this->assertSame($this->tmpFileF, $r->getTmpName("f"));
        $this->assertSame($this->tmpFileG, $r->getTmpName("g"));
        $this->assertSame("", $r->getTmpName("missing"));
    }

    public function testGetCurlFile(): void {
        $r = new Request(withFiles: true);

        $curl = $r->getCurlFile("f");
        $this->assertInstanceOf(CURLFile::class, $curl);

        // verify the CURLFile contains the expected path, mime and post filename
        $this->assertSame($this->tmpFileF, $curl->getFilename());
        $this->assertSame("text/plain", $curl->getMimeType());
        $this->assertSame("a.txt", $curl->getPostFilename());

        // missing file should return null
        $this->assertNull($r->getCurlFile("missing"));
    }

    public function testHasFile(): void {
        $r = new Request(withFiles: true);

        $this->assertTrue($r->hasFile("f"));
        $this->assertTrue($r->hasFile("g"));
        $this->assertFalse($r->hasFile("missing"));
    }

    public function testHasSizeError(): void {
        $r = new Request(withFiles: true);

        $this->assertFalse($r->hasSizeError("f"));
        $this->assertTrue($r->hasSizeError("h"));
        $this->assertTrue($r->hasSizeError("missing"));
    }

    public function testHasExtension(): void {
        $r = new Request([ "img" => "photo.jpg" ], withFiles: true);

        $this->assertTrue($r->hasExtension("f", "txt"));
        $this->assertTrue($r->hasExtension("g", [ "png" ]));

        // also works when value is a plain filename string in request data
        $this->assertTrue($r->hasExtension("img", "jpg"));
        $this->assertFalse($r->hasExtension("missing", "txt"));
    }

    public function testIsValidImage(): void {
        $r = new Request([ "img" => "image.png" ], withFiles: true);

        $this->assertFalse($r->isValidImage("f"));
        $this->assertTrue($r->isValidImage("g"));

        $this->assertTrue($r->isValidImage("img"));
        $this->assertFalse($r->isValidImage("missing"));
    }

    public function testToArray(): void {
        $data = [
            "a" => 1,
            "b" => "x",
            "c" => [ "k" => "v" ],
        ];

        $r = new Request($data);
        $this->assertSame($data, $r->toArray());
    }

    public function testToDictionary(): void {
        $r = new Request([ "a" => 2, "b" => "y" ]);

        $dict = $r->toDictionary();
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertSame(2, $dict->getInt("a"));
        $this->assertSame("y", $dict->getString("b"));
    }

    public function testGetIterator(): void {
        $data = [ "a" => 1, "b" => "x", "c" => ["k" => "v"] ];
        $r = new Request($data);

        $collected = [];
        foreach ($r as $k => $v) {
            $collected[$k] = $v;
        }

        $this->assertSame($data, $collected);
    }

    public function testJsonSerialize(): void {
        $data = [ "a" => 1, "b" => "x" ];
        $r = new Request($data);

        // Ensure jsonSerialize returns the underlying array
        $this->assertSame($data, $r->jsonSerialize());

        // Ensure json_encode uses jsonSerialize
        $this->assertSame(JSON::encode($data), json_encode($r));
    }
}
