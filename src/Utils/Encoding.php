<?php
namespace Framework\Utils;

/**
 * Encoding Utils
 */
class Encoding {

    /** @var array<integer,string> */
    private static array $win1252ToUtf8 = [
        128 => "\xe2\x82\xac",

        130 => "\xe2\x80\x9a",
        131 => "\xc6\x92",
        132 => "\xe2\x80\x9e",
        133 => "\xe2\x80\xa6",
        134 => "\xe2\x80\xa0",
        135 => "\xe2\x80\xa1",
        136 => "\xcb\x86",
        137 => "\xe2\x80\xb0",
        138 => "\xc5\xa0",
        139 => "\xe2\x80\xb9",
        140 => "\xc5\x92",

        142 => "\xc5\xbd",

        145 => "\xe2\x80\x98",
        146 => "\xe2\x80\x99",
        147 => "\xe2\x80\x9c",
        148 => "\xe2\x80\x9d",
        149 => "\xe2\x80\xa2",
        150 => "\xe2\x80\x93",
        151 => "\xe2\x80\x94",
        152 => "\xcb\x9c",
        153 => "\xe2\x84\xa2",
        154 => "\xc5\xa1",
        155 => "\xe2\x80\xba",
        156 => "\xc5\x93",

        158 => "\xc5\xbe",
        159 => "\xc5\xb8",
    ];

    /** @var array<string,string> */
    private static array $brokenUtf8ToUtf8 = [
        "\xc2\x80" => "\xe2\x82\xac",

        "\xc2\x82" => "\xe2\x80\x9a",
        "\xc2\x83" => "\xc6\x92",
        "\xc2\x84" => "\xe2\x80\x9e",
        "\xc2\x85" => "\xe2\x80\xa6",
        "\xc2\x86" => "\xe2\x80\xa0",
        "\xc2\x87" => "\xe2\x80\xa1",
        "\xc2\x88" => "\xcb\x86",
        "\xc2\x89" => "\xe2\x80\xb0",
        "\xc2\x8a" => "\xc5\xa0",
        "\xc2\x8b" => "\xe2\x80\xb9",
        "\xc2\x8c" => "\xc5\x92",

        "\xc2\x8e" => "\xc5\xbd",

        "\xc2\x91" => "\xe2\x80\x98",
        "\xc2\x92" => "\xe2\x80\x99",
        "\xc2\x93" => "\xe2\x80\x9c",
        "\xc2\x94" => "\xe2\x80\x9d",
        "\xc2\x95" => "\xe2\x80\xa2",
        "\xc2\x96" => "\xe2\x80\x93",
        "\xc2\x97" => "\xe2\x80\x94",
        "\xc2\x98" => "\xcb\x9c",
        "\xc2\x99" => "\xe2\x84\xa2",
        "\xc2\x9a" => "\xc5\xa1",
        "\xc2\x9b" => "\xe2\x80\xba",
        "\xc2\x9c" => "\xc5\x93",

        "\xc2\x9e" => "\xc5\xbe",
        "\xc2\x9f" => "\xc5\xb8",
    ];

    /** @var array<string,string> */
    private static array $utf8ToWin1252 = [
       "\xe2\x82\xac" => "\x80",

       "\xe2\x80\x9a" => "\x82",
       "\xc6\x92"     => "\x83",
       "\xe2\x80\x9e" => "\x84",
       "\xe2\x80\xa6" => "\x85",
       "\xe2\x80\xa0" => "\x86",
       "\xe2\x80\xa1" => "\x87",
       "\xcb\x86"     => "\x88",
       "\xe2\x80\xb0" => "\x89",
       "\xc5\xa0"     => "\x8a",
       "\xe2\x80\xb9" => "\x8b",
       "\xc5\x92"     => "\x8c",

       "\xc5\xbd"     => "\x8e",

       "\xe2\x80\x98" => "\x91",
       "\xe2\x80\x99" => "\x92",
       "\xe2\x80\x9c" => "\x93",
       "\xe2\x80\x9d" => "\x94",
       "\xe2\x80\xa2" => "\x95",
       "\xe2\x80\x93" => "\x96",
       "\xe2\x80\x94" => "\x97",
       "\xcb\x9c"     => "\x98",
       "\xe2\x84\xa2" => "\x99",
       "\xc5\xa1"     => "\x9a",
       "\xe2\x80\xba" => "\x9b",
       "\xc5\x93"     => "\x9c",

       "\xc5\xbe"     => "\x9e",
       "\xc5\xb8"     => "\x9f",
    ];



    /**
     * Function Encoding::toUTF8
     *
     * This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
     *
     * It assumes that the encoding of the original string is either Windows-1252 or ISO 8859-1.
     *
     * It may fail to convert characters to UTF-8 if they fall into one of these scenarios:
     *
     * 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
     *    are followed by any of these:  ("group B")
     *                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶•¸¹º»¼½¾¿
     * For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
     * The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB)
     * is also a valid unicode character, and will be left unchanged.
     *
     * 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
     * 3) when any of these: ðñòó  are followed by THREE chars from group B.
     *
     * @param string $text
     * @return string The same string, UTF8 encoded
     */
    public static function toUTF8(string $text): string {
        $max = strlen($text);
        $buf = "";

        for ($i = 0; $i < $max; $i++) {
            $c1 = $text[$i];

            // Should be converted to UTF8, if it's not UTF8 already
            if ($c1 >= "\xc0") {
                $c2 = $i + 1 >= $max? "\x00" : $text[$i + 1];
                $c3 = $i + 2 >= $max? "\x00" : $text[$i + 2];
                $c4 = $i + 3 >= $max? "\x00" : $text[$i + 3];

                // looks like 2 bytes UTF8
                if ($c1 >= "\xc0" && $c1 <= "\xdf") {
                    // yeah, almost sure it's UTF8 already
                    if ($c2 >= "\x80" && $c2 <= "\xbf") {
                        $buf .= $c1 . $c2;
                        $i++;
                    // not valid UTF8. Convert it.
                    } else {
                        $cc1  = chr((int)(ord($c1) / 64)) | "\xc0";
                        $cc2  = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                // looks like 3 bytes UTF8
                } elseif ($c1 >= "\xe0" && $c1 <= "\xef") {
                    // yeah, almost sure it's UTF8 already
                    if ($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf") {
                        $buf .= $c1 . $c2 . $c3;
                        $i = $i + 2;
                    // not valid UTF8.  Convert it.
                    } else {
                        $cc1  = chr((int)(ord($c1) / 64)) | "\xc0";
                        $cc2  = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                // looks like 4 bytes UTF8
                } elseif ($c1 >= "\xf0" && $c1 <= "\xf7") {
                    // yeah, almost sure it's UTF8 already
                    if ($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf") {
                        $buf .= $c1 . $c2 . $c3;
                        $i = $i + 2;
                    // not valid UTF8.  Convert it.
                    } else {
                        $cc1  = chr((int)(ord($c1) / 64)) | "\xc0";
                        $cc2  = ($c1 & "\x3f") | "\x80";
                        $buf .= $cc1 . $cc2;
                    }
                // doesn't look like UTF8, but should be converted
                } else {
                    $cc1  = chr((int)(ord($c1) / 64)) | "\xc0";
                    $cc2  = ($c1 & "\x3f") | "\x80";
                    $buf .= $cc1 . $cc2;
                }
            // needs conversion
            } elseif (($c1 & "\xc0") === "\x80") {
                // found in Windows-1252 special cases
                if (isset(self::$win1252ToUtf8[ord($c1)])) {
                    $buf .= self::$win1252ToUtf8[ord($c1)];
                } else {
                    $cc1  = chr((int)(ord($c1) / 64)) | "\xc0";
                    $cc2  = ($c1 & "\x3f") | "\x80";
                    $buf .= $cc1 . $cc2;
                }
            // it doesn't need conversion
            } else {
                $buf .= $c1;
            }
        }
        return $buf;
    }


    /**
     * Converts to Win-1252
     * @param string $text
     * @return string
     */
    public static function toWin1252(string $text): string {
        return utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), self::toUTF8($text)));
    }

    /**
     * Converts to ISO-8859
     * @param string $text
     * @return string
     */
    public static function toISO8859(string $text): string {
        return self::toWin1252($text);
    }

    /**
     * Converts to Latin-1
     * @param string $text
     * @return string
     */
    public static function toLatin1(string $text): string {
        return self::toWin1252($text);
    }

    /**
     * Fixes the UTF-8 characters
     * @param string $text
     * @return string
     */
    public static function fixUTF8(string $text): string {
        $last = "";
        while ($last !== $text) {
            $last = $text;
            $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
        }
        $text = self::toUTF8(utf8_decode(str_replace(array_keys(self::$utf8ToWin1252), array_values(self::$utf8ToWin1252), $text)));
        return $text;
    }

    /**
     * Fixes the UTF-8 characters
     * @param string $text
     * @return string
     */
    public static function UTF8FixWin1252Chars(string $text): string {
        // If you received an UTF-8 string that was converted from Windows-1252 as it was ISO8859-1
        // (ignoring Windows-1252 chars from 80 to 9F) use this function to fix it.
        // See: http://en.wikipedia.org/wiki/Windows-1252

        return str_replace(array_keys(self::$brokenUtf8ToUtf8), array_values(self::$brokenUtf8ToUtf8), $text);
    }

    /**
     * Removes the BOM character
     * @param string $text Optional.
     * @return string
     */
    public static function removeBOM(string $text = ""): string {
        if (substr($text, 0, 3) === pack("CCC", 0xef, 0xbb, 0xbf)) {
            $text = substr($text, 3);
        }
        return $text;
    }
}
