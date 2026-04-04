<?php
namespace Tests\Utils;

use Framework\Utils\Encoding;

use PHPUnit\Framework\TestCase;

class EncodingTest extends TestCase {

    public function testToUTF8() {
        // latin1 single-byte é -> UTF-8
        $this->assertSame("é", Encoding::toUTF8("\xE9"));

        // windows-1252 single byte 0x80 -> euro sign
        $this->assertSame("€", Encoding::toUTF8("\x80"));

        // already UTF-8 stays the same
        $this->assertSame("abc", Encoding::toUTF8("abc"));
    }

    public function testToWin1252AndAliases() {
        // euro sign should map to single byte 0x80 when converting to Win1252
        $this->assertSame("\x80", Encoding::toWin1252("€"));

        // aliases should delegate to same behaviour
        $this->assertSame(Encoding::toWin1252("€"), Encoding::toISO8859("€"));
        $this->assertSame(Encoding::toWin1252("€"), Encoding::toLatin1("€"));
    }

    public function testFixUTF8() {
        // idempotent: running on already-correct text returns same
        $this->assertSame("hello", Encoding::fixUTF8("hello"));
    }

    public function testUTF8FixWin1252Chars() {
        // specific mapping from broken UTF8 to proper UTF8
        $this->assertSame("€", Encoding::UTF8FixWin1252Chars("\xC2\x80"));
    }

    public function testRemoveBOM() {
        $bom = pack("CCC", 0xef, 0xbb, 0xbf);
        $this->assertSame("abc", Encoding::removeBOM($bom . "abc"));

        // no argument returns empty string
        $this->assertSame("", Encoding::removeBOM(""));
    }

    public function testDecodeUTF8() {
        // latin1 single byte should be converted to UTF-8
        $this->assertSame("?", Encoding::decodeUTF8("\xE9"));

        // already UTF-8 unchanged
        $this->assertSame("abc", Encoding::decodeUTF8("abc"));
    }
}
