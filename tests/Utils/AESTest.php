<?php
namespace Tests\Utils;

use Framework\Utils\AES;

use PHPUnit\Framework\TestCase;

class AESTest extends TestCase {

    public function testToUtf8Bytes() {
        $this->assertSame([ 97, 98, 99 ], AES::toUtf8Bytes("abc"));

        // multi-byte ñ -> %C3%B1
        $this->assertSame([ 195, 177 ], AES::toUtf8Bytes('ñ'));

        // euro sign -> %E2%82%AC
        $this->assertSame([ 226, 130, 172 ], AES::toUtf8Bytes("€"));

        // empty string should return empty array
        $this->assertSame([], AES::toUtf8Bytes(""));
    }

    public function testToHexBytes() {
        // hex string should be converted to byte values
        $this->assertSame([ 10, 27 ], AES::toHexBytes("0a1b"));

        // uppercase hex should work too
        $this->assertSame([ 255, 16 ], AES::toHexBytes("FF10"));

        // odd-length hex should be treated as if it had a leading zero
        $this->assertSame([ 255 ], AES::toHexBytes("ff"));
        $this->assertSame([ 15, 15 ], AES::toHexBytes("0ff"));

        // non-hex characters should be ignored
        $this->assertSame([ 1, 2, 3 ], AES::toHexBytes("1g2h3i"));

        // empty string should return empty array
        $this->assertSame([], AES::toHexBytes(""));
    }

    public function testFromBytes() {
        $this->assertSame("abc", AES::fromBytes([ 97, 98, 99 ]));

        // multi-byte characters
        $this->assertSame("ñ", AES::fromBytes([ 195, 177 ]));
        $this->assertSame("€", AES::fromBytes([ 226, 130, 172 ]));

        // empty array should return empty string
        $this->assertSame("", AES::fromBytes([]));
    }

    public function testEncrypt() {
        // empty input remains empty
        $key = array_fill(0, 16, 1);
        $this->assertSame([], AES::encrypt([], $key));

        // small payload: double-encrypt returns original (CTR keystream XOR twice)
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
