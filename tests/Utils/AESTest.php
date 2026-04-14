<?php
namespace Tests\Utils;

use Framework\Utils\AES;

use PHPUnit\Framework\TestCase;

class AESTest extends TestCase {

    /** @dataProvider providerToUtf8Bytes */
    public function testToUtf8Bytes(string $input, array $expected) {
        $this->assertSame($expected, AES::toUtf8Bytes($input));
    }

    public static function providerToUtf8Bytes() {
        return [
            "ascii" => [ "abc", [ 97, 98, 99 ] ],
            "ñ"     => [ "ñ", [ 195, 177 ] ],
            "€"     => [ "€", [ 226, 130, 172 ] ],
            "empty" => [ "", [] ],
        ];
    }


    /** @dataProvider providerToHexBytes */
    public function testToHexBytes(string $input, array $expected) {
        $this->assertSame($expected, AES::toHexBytes($input));
    }

    public static function providerToHexBytes() {
        return [
            "hex"   => [ "0a1b", [ 10, 27 ] ],
            "upper" => [ "FF10", [ 255, 16 ] ],
            "odd"   => [ "ff", [ 255 ] ],
            "odd2"  => [ "0ff", [ 15, 15 ] ],
            "noise" => [ "1g2h3i", [ 1, 2, 3 ] ],
            "empty" => [ "", [] ],
        ];
    }


    /** @dataProvider providerFromBytes */
    public function testFromBytes(array $input, string $expected) {
        $this->assertSame($expected, AES::fromBytes($input));
    }

    public static function providerFromBytes() {
        return [
            "ascii" => [ [ 97, 98, 99 ], "abc" ],
            "ñ"     => [ [ 195, 177 ], "ñ" ],
            "€"     => [ [ 226, 130, 172 ], "€" ],
            "empty" => [ [], "" ],
        ];
    }


    public function testEncrypt() {
        // empty input remains empty
        $key = array_fill(0, 16, 1);
        $this->assertSame([], AES::encrypt([], $key));

        // small payload: double-encrypt returns original (CTR key-stream XOR twice)
        $payload = [ 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ];
        $cipher = AES::encrypt($payload, $key);
        $this->assertNotSame($payload, $cipher);
        $plain = AES::encrypt($cipher, $key);
        $this->assertSame($payload, $plain);

        // longer payload across multiple counter blocks
        $long = range(0, 63);
        $enc = AES::encrypt($long, $key);
        $this->assertNotSame($long, $enc);
        $dec = AES::encrypt($enc, $key);
        $this->assertSame($long, $dec);
    }
}
