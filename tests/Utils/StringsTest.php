<?php
// spell-checker: ignore abbab bcdef preabc tést xyxyxy
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Utils\Strings;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

enum TestStringEnum implements Enum {
    use IsEnum;

    case None;
    case String;
    case Number;
}

class StringsTest extends TestCase {
    use TestHelpers;

    #[DataProvider("providerIsString")]
    public function testIsString(mixed $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isString($value));
    }

    public static function providerIsString(): array {
        return [
            "empty"   => [ "", true ],
            "char"    => [ "x", true],
            "numeric" => [ "123", true ],
            "integer" => [ 1, false ],
            "null"    => [ null, false ],
            "boolean" => [ false, false ],
            "object"  => [ new stdClass(), false ],
        ];
    }


    #[DataProvider("providerIsValid")]
    public function testIsValid(mixed $string, bool $expected): void {
        $this->assertEquals($expected, Strings::isValid($string));
    }

    public static function providerIsValid(): array {
        return [
            "empty"   => [ "", false ],
            "space"   => [ " ", false ],
            "char"    => [ "x", true],
            "numeric" => [ "123", true ],
            "spaced"  => [ " xx ", true ],
            "null"    => [ null, false ],
            "boolean" => [ false, false ],
            "object"  => [ new stdClass(), false ],
        ];
    }


    #[DataProvider("providerToString")]
    public function testToString(mixed $value, string $expected): void {
        $this->assertEquals($expected, Strings::toString($value));
    }

    public static function providerToString(): array {
        return [
            "string"  => [ "x", "x" ],
            "integer" => [ 123, "123" ],
            "float"   => [ 12.3, "12.3" ],
            "object"  => [ new stdClass(), "" ],
            "date"    => [ Date::createTime(1, 1, 2020, 12, 34, 56), "2020-01-01 12:34:56" ],
            "enum"    => [ TestStringEnum::String, "String" ],
        ];
    }


    #[DataProvider("providerTrim")]
    public function testTrim(mixed $value, string $expected): void {
        $this->assertEquals($expected, Strings::trim($value));
    }

    public static function providerTrim(): array {
        return [
            "space_both" => [ " a ", "a" ],
            "space_left" => [ " a", "a" ],
            "integer"    => [ 1, "1" ],
            "float"      => [ 1.2, "1.2" ],
            "null"       => [ null, "" ],
        ];
    }


    #[DataProvider("providerNormalized")]
    public function testNormalized(mixed $value, string $expected): void {
        $this->assertEquals($expected, Strings::normalized($value));
    }

    public static function providerNormalized(): array {
        return [
            "with_whitespace" => [ " x\r\nline ", "x\nline" ],
            "single_char"     => [ "a", "a" ],
            "null"            => [ null, "" ],
        ];
    }


    #[DataProvider("providerLength")]
    public function testLength(mixed $value, int $expected): void {
        $this->assertEquals($expected, Strings::length($value));
    }

    public static function providerLength(): array {
        return [
            "utf8_char" => [ "oración", 7 ],
            "emoji"     => [ "😄", 1 ],
            "mixed"     => [ "hello 😄", 7 ],
            "empty"     => [ "", 0 ],
        ];
    }


    #[DataProvider("providerIsEqual")]
    public function testIsEqual(mixed $value, mixed $compare, bool $ignoreCase, bool $trim, bool $expected): void {
        $this->assertEquals($expected, Strings::isEqual($value, $compare, $ignoreCase, $trim));
    }

    public static function providerIsEqual(): array {
        return [
            "default_insensitive_trim" => [ " A ", "a", true, true, true ],
            "case_sensitive"           => [ " A ", "a", false, true, false ],
            "no_trim"                  => [ " A ", "a", true, false, false ],
            "case_sensitive_no_trim"   => [ "A ", "A", false, false, false ],
            "numeric_conversion"       => [ 123, " 123 ", true, true, true ],
            "date_conversion"          => [ Date::createTime(1, 1, 2020, 12, 34, 56), "2020-01-01 12:34:56", true, true, true ],
            "enum_conversion"          => [ TestStringEnum::String, "String", true, true, true ],
            "null_empty_string"        => [ null, "", true, true, true ],
            "false_empty_string"       => [ false, "", true, true, true ],
            "different_strings"        => [ "A", "B", false, true, false ],
        ];
    }


    #[DataProvider("providerEquals")]
    public function testEquals(mixed $value, array $compare, bool $expected): void {
        $this->assertEquals($expected, Strings::equals($value, ...$compare));
    }

    public static function providerEquals(): array {
        return [
            "empty_equals_empty"       => [ "", [ "" ], true ],
            "one_equals_one"           => [ "one", [ "one" ], true ],
            "one_equals_in_list"       => [ "one", [ "two", "one" ], true ],
            "one_equals_first_in_list" => [ "one", [ "one", "two" ], true ],

            "empty_not_dash"           => [ "", [ "-" ], false ],
            "empty_not_in_list"        => [ "", [ "two", "three" ], false ],
            "one_not_in_list"          => [ "one", [ "two", "three" ], false ],
        ];
    }


    #[DataProvider("providerEqualsCaseInsensitive")]
    public function testEqualsCaseInsensitive(mixed $value, array $compare, bool $expected): void {
        $this->assertEquals($expected, Strings::equalsCaseInsensitive($value, ...$compare));
    }

    public static function providerEqualsCaseInsensitive(): array {
        return [
            "empty_equals_empty"      => [ "", [ "" ], true ],
            "one_equals_case_variant" => [ "One", [ "oNe" ], true ],
            "one_equals_in_list"      => [ "one", [ "one", "two" ], true ],
            "one_equals_case_in_list" => [ "One", [ "oNe", "two" ], true ],

            "empty_not_dash"          => [ "", [ "-" ], false ],
            "empty_not_in_list"       => [ "", [ "two", "three" ], false ],
            "one_not_two"             => [ "One", [ "Two" ], false ],
            "one_not_in_three_list"   => [ "One", [ "Two", "Three" ], false ],
        ];
    }


    #[DataProvider("providerContains")]
    public function testContains(string $value, string|array $needle, bool $ignoreCase, bool $atLeastOne, bool $expected): void {
        $this->assertEquals($expected, Strings::contains($value, $needle, $ignoreCase, $atLeastOne));
    }

    public static function providerContains(): array {
        return [
            "one_ci_ok"     => [ "Hello World", "world", true, true, true ],
            "one_cs_no"     => [ "Hello World", "world", false, true, false ],

            "arr_any_ok"    => [ "abc", [ "x", "b" ], false, true, true ],
            "arr_any_no"    => [ "abc", [ "x", "y" ], false, true, false ],

            "arr_all_ok"    => [ "abc", [ "a", "b" ], false, false, true ],
            "arr_all_no"    => [ "abc", [ "a", "x" ], false, false, false ],

            "arr_ci_all_ok" => [ "AbCd", [ "a", "C" ], true, false, true ],
            "arr_one_ok"    => [ "abc", [ "b" ], false, true, true ],
        ];
    }


    #[DataProvider("providerStartsWith")]
    public function testStartsWith(string $value, array $needles, bool $expected): void {
        $this->assertEquals($expected, Strings::startsWith($value, ...$needles));
    }

    public static function providerStartsWith(): array {
        return [
            "single_match"        => [ "prefix_value", [ "prefix" ], true ],
            "single_no_match"     => [ "nope", [ "pre" ], false ],
            "multiple_any_match"  => [ "prefix_value", [ "no", "prefix" ], true ],
            "multiple_none_match" => [ "nope", [ "pre", "xx" ], false ],
        ];
    }


    #[DataProvider("providerStartsWithCaseInsensitive")]
    public function testStartsWithCaseInsensitive(string $value, array $needles, bool $expected): void {
        $this->assertEquals($expected, Strings::startsWithCaseInsensitive($value, ...$needles));
    }

    public static function providerStartsWithCaseInsensitive(): array {
        return [
            "single_match"       => [ "AbC", [ "a" ], true ],
            "multiple_any_match" => [ "AbC", [ "x", "A" ], true ],
            "multiple_none"      => [ "AbC", [ "x", "y" ], false ],
        ];
    }


    #[DataProvider("providerEndsWith")]
    public function testEndsWith(string $value, array $needles, bool $expected): void {
        $this->assertEquals($expected, Strings::endsWith($value, ...$needles));
    }

    public static function providerEndsWith(): array {
        return [
            "single_match"        => [ "file.php", [ ".php" ], true ],
            "single_no_match"     => [ "file.txt", [ ".php" ], false ],
            "multiple_any_match"  => [ "index.html", [ ".php", ".html" ], true ],
            "case_sensitive_no"   => [ "readme.MD", [ ".md", ".txt" ], false ],
            "case_sensitive_yes"  => [ "readme.md", [ ".md", ".txt" ], true ],
            "multiple_none_match" => [ "file", [ ".php", ".html" ], false ],
        ];
    }


    #[DataProvider("providerEndsWithCaseInsensitive")]
    public function testEndsWithCaseInsensitive(string $value, array $needles, bool $expected): void {
        $this->assertEquals($expected, Strings::endsWithCaseInsensitive($value, ...$needles));
    }

    public static function providerEndsWithCaseInsensitive(): array {
        return [
            "single_match"        => [ "AbC", [ "c" ], true ],
            "multiple_any_match"  => [ "readme.MD", [ ".md", ".txt" ], true ],
            "multiple_html_match" => [ "index.HTML", [ ".php", ".html" ], true ],
            "multiple_none_match" => [ "file", [ ".php", ".txt" ], false ],
        ];
    }


    #[DataProvider("providerMatch")]
    public function testMatch(string $value, string $pattern, bool $expected): void {
        $result = $this->runWithSuppressedWarnings(
            fn() => Strings::match($value, $pattern),
            suppress: true,
        );
        $this->assertEquals($expected, $result);
    }

    public static function providerMatch(): array {
        return [
            "anchored_numeric_match"   => [ "123", "/^[0-9]+$/", true ],
            "anchored_numeric_no"      => [ "a1", "/^[0-9]+$/", false ],
            "unanchored_digit_search"  => [ "abc123", "/\\d+/", true ],
            "case_insensitive_match"   => [ "HELLO", "/hello/i", true ],
            "pattern_matches_nothing"  => [ "b", "/^a$/", false ],
            "invalid_regexp_plain"     => [ "anything", "invalid", false ],
            "invalid_regexp_delimited" => [ "anything", "/]invalid/", false ],
        ];
    }


    #[DataProvider("providerGetAllMatches")]
    public function testGetAllMatches(string $value, string $pattern, array $expected): void {
        $this->assertEquals($expected, Strings::getAllMatches($value, $pattern));
    }

    public static function providerGetAllMatches(): array {
        return [
            "simple_digit_matches" => [ "a1b22", "/\\d+/", [ "1", "22" ] ],
            "no_matches"           => [ "abc", "/\\d+/", [] ],

            "with_groups"          => [ "abbab", "/(a)(b+)/", [
                "abb", "ab", // full matches
                "a", "a",    // group 1 matches
                "bb", "b"    // group 2 matches
            ] ],

            "single_group"         => [ "a1b22", "/(\\d+)/", [
                "1", "22", // full matches
                "1", "22"  // group matches
            ] ],

            "invalid_regexp"       => [ "anything", "/]invalid/", [] ],
        ];
    }


    #[DataProvider("providerOnlyOneCharacter")]
    public function testOnlyOneCharacter(string $value, string $character, bool $expected): void {
        $this->assertEquals($expected, Strings::onlyOneCharacter($value, $character));
    }

    public static function providerOnlyOneCharacter(): array {
        return [
            "valid_repeated_char"      => [ "aaa", "a", true ],
            "empty_string"             => [ "", "a", false ],
            "multi_char_target"        => [ "aaa", "aa", false ],
            "contains_other_character" => [ "aba", "a", false ],
            "contains_spaces"          => [ "a b a", "a", false ],
        ];
    }


    #[DataProvider("providerCompare")]
    public function testCompare(string $value, string $compare, bool $orderAsc, bool $ignoreCase, int $expectedSign): void {
        $actual = Strings::compare($value, $compare, $orderAsc, $ignoreCase);
        $this->assertSame($expectedSign, $actual <=> 0);
    }

    public static function providerCompare(): array {
        return [
            // basic ordering
            "b_gt_a_asc" => [ "b", "a", true, false, 1 ],
            "a_lt_b_asc" => [ "a", "b", true, false, -1 ],

            // equal strings -> zero
            "same_eq_same" => [ "same", "same", true, false, 0 ],

            // reverse ordering when orderAsc = false
            "b_lt_a_desc" => [ "b", "a", false, false, -1 ],
            "a_gt_b_desc" => [ "a", "b", false, false, 1 ],

            // case-insensitive comparisons
            "a_lt_B_ci" => [ "a", "B", true, true, -1 ],
            "B_gt_a_ci" => [ "B", "a", true, true, 1 ],

            // combination: orderAsc = false with case-insensitive
            "a_gt_B_desc_ci" => [ "a", "B", false, true, 1 ],
            "B_lt_a_desc_ci" => [ "B", "a", false, true, -1 ],
        ];
    }


    #[DataProvider("providerGetLetter")]
    public function testGetLetter(int $index, bool $uppercase, string $expected): void {
        $this->assertEquals($expected, Strings::getLetter($index, $uppercase));
    }

    public static function providerGetLetter(): array {
        return [
            "upper_a"          => [ 0, true, "A" ],
            "upper_c"          => [ 2, true, "C" ],
            "lower_a"          => [ 0, false, "a" ],
            "lower_c"          => [ 2, false, "c" ],
            "invalid_high"     => [ 200, true, "" ],
            "invalid_negative" => [ -1, true, "" ],
        ];
    }


    #[DataProvider("providerGetNumber")]
    public function testGetNumber(string $value, int $expected): void {
        $this->assertEquals($expected, Strings::getNumber($value));
    }

    public static function providerGetNumber(): array {
        return [
            "upper_a"        => [ "A", 1 ],
            "lower_c"        => [ "c", 3 ],
            "invalid_dash"   => [ "-", 0 ],
            "invalid_double" => [ "AA", 0 ],
            "invalid_cc"     => [ "cc", 0 ],
        ];
    }


    #[DataProvider("providerRepeat")]
    public function testRepeat(string $value, int $count, string $expected): void {
        $this->assertEquals($expected, Strings::repeat($value, $count));
    }

    public static function providerRepeat(): array {
        return [
            "single_char_x3" => [ "x", 3, "xxx" ],
            "multi_char_x3"  => [ "xy", 3, "xyxyxy" ],
            "zero_count"     => [ "x", 0, "" ],
            "negative_count" => [ "x", -1, "" ],
        ];
    }


    public function testRandom(): void {
        $r = Strings::random(5);
        $this->assertIsString($r);
        $this->assertEquals(5, strlen($r));
    }


    #[DataProvider("providerRandomChar")]
    public function testRandomChar(string $chars, string|array $expected): void {
        $actual = Strings::randomChar($chars);
        if (is_array($expected)) {
            $this->assertContains($actual, $expected);
        } else {
            $this->assertEquals($expected, $actual);
        }
    }

    public static function providerRandomChar(): array {
        return [
            "empty"  => [ "", "" ],
            "single" => [ "a", "a" ],
            "many"   => [ "abc", [ "a", "b", "c" ] ],
        ];
    }


    #[DataProvider("providerRandomCode")]
    public function testRandomCode(?int $length, ?string $set, int $expectedLength, ?string $expectedPattern, ?string $expectedValue = null): void {
        if ($length === null && $set === null) {
            $code = Strings::randomCode();
        } elseif ($set === null) {
            $code = Strings::randomCode($length);
        } else {
            $code = Strings::randomCode($length, $set);
        }

        $this->assertIsString($code);
        $this->assertEquals($expectedLength, strlen($code));

        if ($expectedValue !== null) {
            $this->assertEquals($expectedValue, $code);
        }

        if ($expectedPattern !== null && $code !== "") {
            $this->assertMatchesRegularExpression($expectedPattern, $code);
        }
    }

    public static function providerRandomCode(): array {
        return [
            "default_values"     => [ null, null, 8, '/^[a-zA-Z0-9]+$/', null ],
            "letters_and_digits" => [ 6, "ld", 6, '/^[a-z0-9]+$/', null ],
            "letters_any_case"   => [ 10, "a", 10, '/^[a-zA-Z]+$/', null ],
            "lowercase_only"     => [ 8, "l", 8, '/^[a-z]+$/', null ],
            "uppercase_only"     => [ 8, "u", 8, '/^[A-Z]+$/', null ],
            "digits_only"        => [ 8, "d", 8, '/^[0-9]+$/', null ],
            "symbols_only"       => [ 8, "s", 8, '/^[!@#\$%&\*\?]+$/', null ],
            "empty_set"          => [ 8, "", 0, null, "" ],
            "invalid_set"        => [ 8, "x", 0, null, "" ],
        ];
    }


    #[DataProvider("providerToNumber")]
    public function testToNumber(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toNumber($value));
    }

    public static function providerToNumber(): array {
        return [
            "mixed_alnum" => [ "a1b2", "12" ],
            "no_digits"   => [ "abc", "" ],
        ];
    }


    #[DataProvider("providerReplace")]
    public function testReplace(string $value, string|array $search, string|array|null $replace, string $expected): void {
        if (is_array($search) && $replace === null) {
            $this->assertEquals($expected, Strings::replace($value, $search));
        } else {
            $this->assertEquals($expected, Strings::replace($value, $search, $replace));
        }
    }

    public static function providerReplace(): array {
        return [
            // simple scalar replacement
            "scalar_single" => [ "foo", "foo", "bar", "bar" ],
            "scalar_multi"  => [ "foo foo", "foo", "bar", "bar bar" ],

            // mapping replacement: keys replaced by their values
            "mapping_match"    => [ "a:b", [ "a" => "one", "b" => "two" ], null, "one:two" ],
            "mapping_no_match" => [ "abc", [ "x" => "y" ], null, "abc" ],

            // array search with single replacement string
            "array_search_single_replace" => [ "abc", [ "a", "b" ], "X", "XXc" ],

            // array search with array replacement
            "array_search_array_replace" => [ "abc", [ "a", "b" ], [ "x", "y" ], "xyc" ],

            // empty replace
            "empty_replace" => [ "abc", "a", "", "bc" ],
        ];
    }


    #[DataProvider("providerReplaceStart")]
    public function testReplaceStart(string $value, string $search, string $replace, string $expected): void {
        $this->assertEquals($expected, Strings::replaceStart($value, $search, $replace));
    }

    public static function providerReplaceStart(): array {
        return [
            "basic_ok"      => [ "fooBAR", "foo", "bar", "barBAR" ],
            "no_prefix"     => [ "fooBAR", "no", "x", "fooBAR" ],
            "full_match"    => [ "foo", "foo", "bar", "bar" ],
            "empty_in"      => [ "", "foo", "bar", "" ],
            "case_ok"       => [ "FooBAR", "Foo", "bar", "barBAR" ],
            "case_no_match" => [ "fooBAR", "Foo", "bar", "fooBAR" ],
        ];
    }


    #[DataProvider("providerReplaceEnd")]
    public function testReplaceEnd(string $value, string $search, string $replace, string $expected): void {
        $this->assertEquals($expected, Strings::replaceEnd($value, $search, $replace));
    }

    public static function providerReplaceEnd(): array {
        return [
            "basic_suffix_replacement" => [ "fooBAR", "BAR", "BAZ", "fooBAZ" ],
            "no_suffix_noop"           => [ "fooBAR", "no", "x", "fooBAR" ],
            "full_string_replacement"  => [ "bar", "bar", "baz", "baz" ],
            "empty_input"              => [ "", "x", "y", "" ],
            "case_sensitive_match"     => [ "fooBar", "Bar", "BAZ", "fooBAZ" ],
            "case_sensitive_no_match"  => [ "fooBAR", "Bar", "BAZ", "fooBAR" ],
        ];
    }


    #[DataProvider("providerReplacePattern")]
    public function testReplacePattern(string $value, string|array $pattern, string|array $replace, ?int $limit, string $expected): void {
        if ($limit === null) {
            $actual = Strings::replacePattern($value, $pattern, $replace);
        } else {
            $actual = Strings::replacePattern($value, $pattern, $replace, $limit);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerReplacePattern(): array {
        return [
            "vowel_rep"      => [ "hello", "/[eo]/", "_", null, "h_ll_" ],
            "digit_rep"      => [ "a1b22", "/\\d+/", "N", null, "aNbN" ],
            "limit_first"    => [ "a1b22", "/\\d+/", "N", 1, "aNb22" ],
            "group_back_ref" => [ "a1b2", "/(\\d)/", "[$1]", null, "a[1]b[2]" ],
            "ci_rep"         => [ "hello", "/h/i", "J", null, "Jello" ],
            "arr_pat_arr_rep"=> [ "hello123", [ "/[eo]/", "/\\d+/" ], [ "_", "N" ], null, "h_ll_N" ],
        ];
    }


    #[DataProvider("providerReplaceCallback")]
    public function testReplaceCallback(string $value, string|array $pattern, callable $callback, ?int $limit, string $expected): void {
        if ($limit === null) {
            $actual = Strings::replaceCallback($value, $pattern, $callback);
        } else {
            $actual = Strings::replaceCallback($value, $pattern, $callback, $limit);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerReplaceCallback(): array {
        return [
            "basic_wrap_matches" => [
                "a1b2",
                "/(\\d+)/",
                function($m) { return "[" . $m[0] . "]"; },
                null,
                "a[1]b[2]"
            ],
            "limit_first_only" => [
                "a1b22",
                "/(\\d+)/",
                function($m) { return "[" . $m[0] . "]"; },
                1,
                "a[1]b22"
            ],
            "double_numeric_values" => [
                "a2b3",
                "/(\\d+)/",
                function($m) { return (string)((int)$m[0] * 2); },
                null,
                "a4b6"
            ],
            "array_patterns" => [
                "a1e2",
                ["/[ae]/", "/(\\d)/"],
                function($m) {
                    if (ctype_digit($m[0])) {
                        return "{" . $m[0] . "}";
                    }
                    return strtoupper($m[0]);
                },
                null,
                "A{1}E{2}"
            ],
        ];
    }


    #[DataProvider("providerStripStart")]
    public function testStripStart(string $value, array $needles, string $expected): void {
        $this->assertEquals($expected, Strings::stripStart($value, ...$needles));
    }

    public static function providerStripStart(): array {
        return [
            "basic_start"       => [ "pre_value", [ "pre_" ], "value" ],
            "multi_first_match" => [ "prefix_value", [ "prefix_", "pre" ], "value" ],
            "no_match"          => [ "prefix_value", [ "x", "y" ], "prefix_value" ],
            "empty_input"       => [ "", [ "pre" ], "" ],
            "order_short_first" => [ "prefix_value", [ "pre", "prefix_" ], "fix_value" ],
        ];
    }


    #[DataProvider("providerStripEnd")]
    public function testStripEnd(string $value, array $needles, string $expected): void {
        $this->assertEquals($expected, Strings::stripEnd($value, ...$needles));
    }

    public static function providerStripEnd(): array {
        return [
            "basic_end"   => [ "pre_suf", [ "_suf" ], "pre" ],
            "multi_any"   => [ "file.php", [ ".php", ".txt" ], "file" ],
            "none_match"  => [ "file.php", [ ".x", ".y" ], "file.php" ],
            "empty_input" => [ "", [ "x" ], "" ],
            "short_first" => [ "file.txt", [ "t", ".txt" ], "file.tx" ],
        ];
    }


    #[DataProvider("providerStripStartEnd")]
    public function testStripStartEnd(string $value, string $start, string $end, string $expected): void {
        $this->assertEquals($expected, Strings::stripStartEnd($value, $start, $end));
    }

    public static function providerStripStartEnd(): array {
        return [
            "basic_start_end_removal" => [ "[mid]", "[", "]", "mid" ],
            "longer_delimiters"       => [ "<<text>>", "<<", ">>", "text" ],
            "empty_input"             => [ "", "[", "]", "" ],
            "only_start_matches"      => [ "pre_foo", "pre_", "]", "foo" ],
            "only_end_matches"        => [ "bar_suf", "[", "_suf", "bar" ],
        ];
    }


    #[DataProvider("providerPadLeft")]
    public function testPadLeft(string $value, int $length, string $needle, string $expected): void {
        $this->assertEquals($expected, Strings::padLeft($value, $length, $needle));
    }

    public static function providerPadLeft(): array {
        return [
            "basic_numeric_padding"  => [ "1", 3, "0", "001" ],
            "length_less_than_value" => [ "abcd", 3, "0", "abcd" ],
            "length_equal_value"     => [ "abcd", 4, "0", "abcd" ],
            "multi_character_needle" => [ "1", 5, "ab", "abab1" ],
            "default_space_padding"  => [ "x", 3, " ", "  x" ],
        ];
    }


    #[DataProvider("providerPadRight")]
    public function testPadRight(string $value, int $length, ?string $needle, string $expected): void {
        if ($needle === null) {
            $this->assertEquals($expected, Strings::padRight($value, $length));
        } else {
            $this->assertEquals($expected, Strings::padRight($value, $length, $needle));
        }
    }

    public static function providerPadRight(): array {
        return [
            "basic_right_padding"    => [ "1", 3, " ", "1  " ],
            "length_less_than_value" => [ "hello", 3, " ", "hello" ],
            "length_equal_value"     => [ "hello", 5, " ", "hello" ],
            "multi_character_needle" => [ "1", 4, "xy", "1xyx" ],
            "default_space_padding"  => [ "x", 3, null, "x  " ],
        ];
    }


    #[DataProvider("providerAddPrefix")]
    public function testAddPrefix(string $value, string $prefix, string $expected): void {
        $this->assertEquals($expected, Strings::addPrefix($value, $prefix));
    }

    public static function providerAddPrefix(): array {
        return [
            "empty_string"           => [ "", "pre_", "" ],
            "empty_prefix"           => [ "x", "", "x" ],
            "missing_prefix"         => [ "x", "pre_", "pre_x" ],
            "no_duplicate_prefix"    => [ "pre_x", "pre_", "pre_x" ],
            "multi_character_prefix" => [ "x", "Mr ", "Mr x" ],
        ];
    }


    #[DataProvider("providerAddSuffix")]
    public function testAddSuffix(string $value, string $suffix, string $expected): void {
        $this->assertEquals($expected, Strings::addSuffix($value, $suffix));
    }

    public static function providerAddSuffix(): array {
        return [
            "empty_string"           => [ "", "_suf", "" ],
            "empty_suffix"           => [ "x", "", "x" ],
            "missing_suffix"         => [ "x", "_suf", "x_suf" ],
            "no_duplicate_suffix"    => [ "x_suf", "_suf", "x_suf" ],
            "multi_character_suffix" => [ "x", " Jr.", "x Jr." ],
        ];
    }


    #[DataProvider("providerAddPrefixSuffix")]
    public function testAddPrefixSuffix(string $value, string $prefix, string $suffix, string $expected): void {
        $this->assertEquals($expected, Strings::addPrefixSuffix($value, $prefix, $suffix));
    }

    public static function providerAddPrefixSuffix(): array {
        return [
            "empty"    => [ "", "pre_", "_suf", "" ],
            "add_both" => [ "x", "pre_", "_suf", "pre_x_suf" ],
            "has_pre"  => [ "pre_x", "pre_", "_suf", "pre_x_suf" ],
            "has_suf"  => [ "x_suf", "pre_", "_suf", "pre_x_suf" ],
            "has_both" => [ "pre_x_suf", "pre_", "_suf", "pre_x_suf" ],
        ];
    }


    #[DataProvider("providerSubstring")]
    public function testSubstring(string $value, int $start, ?int $length, bool $asUtf8, string $expected): void {
        if ($length === null) {
            $actual = Strings::substring($value, $start, asUtf8: $asUtf8);
        } else {
            $actual = Strings::substring($value, $start, $length, $asUtf8);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerSubstring(): array {
        return [
            "basic_with_length"    => [ "abcdef", 1, 3, false, "bcd" ],
            "without_length"       => [ "abcdef", 1, null, false, "bcdef" ],
            "negative_start"       => [ "abcdef", -3, null, false, "def" ],
            "utf8_aware_substring" => [ "tést", 1, 2, true, "és" ],
        ];
    }


    #[DataProvider("providerSubstringAfter")]
    public function testSubstringAfter(string $value, string $needle, bool $useFirst, string $expected): void {
        $this->assertEquals($expected, Strings::substringAfter($value, $needle, $useFirst));
    }

    public static function providerSubstringAfter(): array {
        return [
            "default_uses_last"      => [ "a.b.c", ".", false, "c" ],
            "use_first_occurrence"   => [ "a.b.c", ".", true, "b.c" ],
            "needle_at_end"          => [ "a.", ".", false, "" ],
            "needle_not_found"       => [ "abc", ".", false, "abc" ],
            "empty_needle_use_first" => [ "abc", "", true, "abc" ],
        ];
    }


    #[DataProvider("providerSubstringBefore")]
    public function testSubstringBefore(string $value, string $needle, ?bool $useFirst, string $expected): void {
        if ($useFirst === null) {
            $actual = Strings::substringBefore($value, $needle);
        } else {
            $actual = Strings::substringBefore($value, $needle, $useFirst);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerSubstringBefore(): array {
        return [
            "default_uses_first" => [ "a.b.c", ".", null, "a" ],
            "use_last"           => [ "a.b.c", ".", false, "a.b" ],
            "needle_not_found"   => [ "abc", ".", true, "abc" ],
            "needle_at_start"    => [ ".a", ".", true, ".a" ],
            "empty_needle"       => [ "abc", "", true, "abc" ],
        ];
    }


    #[DataProvider("providerSubstringBetween")]
    public function testSubstringBetween(string $value, string $start, string $end, string $expected): void {
        $this->assertEquals($expected, Strings::substringBetween($value, $start, $end));
    }

    public static function providerSubstringBetween(): array {
        return [
            "basic_between"      => [ "x[start]mid[end]y", "[start]", "[end]", "mid" ],
            "longer_delimiters"  => [ "<<text>>", "<<", ">>", "text" ],
            "missing_delimiters" => [ "nope", "[", "]", "nope" ],
            "empty_string"       => [ "", "[", "]", "" ],
        ];
    }


    #[DataProvider("providerSplit")]
    public function testSplit(mixed $value, string $needle, bool $trim, bool $skipEmpty, array $expected): void {
        $this->assertEquals($expected, Strings::split($value, $needle, trim: $trim, skipEmpty: $skipEmpty));
    }

    public static function providerSplit(): array {
        return [
            "basic_split_trim_skip_empty" => [ "a,,b", ",", true, true, [ "a", "b" ] ],
            "empty_string"                => [ "", ",", true, true, [] ],
            "empty_needle"                => [ "abc", "", true, true, [] ],
            "array_input_unchanged"       => [ [ "x", "y" ], ",", true, true, [ "x", "y" ] ],
            "needle_not_present"          => [ "abc", "|", true, true, [ "abc" ] ],

            "raw_no_trim_no_skip"         => [ " a , , b ", ",", false, false, [ " a ", " ", " b " ] ],
            "raw_trim_no_skip"            => [ " a , , b ", ",", true, false, [ "a", "", "b" ] ],
            "raw_no_trim_skip"            => [ " a , , b ", ", ", false, true, [ " a ", "b " ] ],
            "raw_trim_skip"               => [ " a , , b ", ",", true, true, [ "a", "b" ] ],

            "multi_character_needle"      => [ "a--b--c", "--", true, true, [ "a", "b", "c" ] ],

            "trailing_sep_keep_empty"     => [ "a,b,", ",", false, false, [ "a", "b", "" ] ],
            "trailing_sep_skip_empty"     => [ "a,b,", ",", false, true, [ "a", "b" ] ],

            "needle_equals_full_keep"     => [ ",", ",", false, false, [ "", "" ] ],
            "needle_equals_full_skip"     => [ ",", ",", false, true, [] ],
        ];
    }


    #[DataProvider("providerSplitToWords")]
    public function testSplitToWords(string $value, array $expectedContains, ?array $expectedExact = null): void {
        $words = Strings::splitToWords($value);
        foreach ($expectedContains as $expectedWord) {
            $this->assertContains($expectedWord, $words);
        }
        if ($expectedExact !== null) {
            $this->assertEquals($expectedExact, $words);
        }
    }

    public static function providerSplitToWords(): array {
        return [
            "hello_world" => [ "Hello, world!", [ "Hello", "world" ], null ],
            "punctuation" => [ "Wait... what?", [ "Wait", "what" ], null ],
            "empty"       => [ "", [], [] ],
        ];
    }


    #[DataProvider("providerJoin")]
    public function testJoin(mixed $value, ?string $glue, ?bool $withoutEmpty, string $expected): void {
        if ($glue === null && $withoutEmpty === null) {
            $actual = Strings::join($value);
        } elseif ($glue === null) {
            $actual = Strings::join($value, withoutEmpty: $withoutEmpty);
        } elseif ($withoutEmpty === null) {
            $actual = Strings::join($value, $glue);
        } else {
            $actual = Strings::join($value, $glue, withoutEmpty: $withoutEmpty);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerJoin(): array {
        return [
            "basic_with_glue"      => [ [ "a", "b" ], ", ", null, "a, b" ],
            "without_glue"         => [ [ "a", "b" ], null, null, "ab" ],
            "without_empty"        => [ [ "a", "", "b" ], ",", true, "a,b" ],

            "numeric_array"        => [ [ 1, 2 ], ", ", null, "1, 2" ],
            "numeric_with_zero"    => [ [ 1, 0, 2 ], ", ", true, "1, 2" ],
            "float_array"          => [ [ 1.2, 2.3 ], ", ", null, "1.2, 2.3" ],

            "non_array_string"     => [ "x", null, null, "x" ],
            "non_array_non_string" => [ 123, null, null, "" ],
        ];
    }


    #[DataProvider("providerJoinKeys")]
    public function testJoinKeys(mixed $value, string $expected): void {
        $this->assertEquals($expected, Strings::joinKeys($value));
    }

    public static function providerJoinKeys(): array {
        return [
            "assoc_keys"        => [ [ "a" => 1, "b" => 2 ], "ab" ],
            "list_numeric_keys" => [ [ 1, 2 ], "01" ],
            "string_input"      => [ "x", "x" ],
            "non_array_input"   => [ 123, "" ],
        ];
    }


    #[DataProvider("providerJoinValues")]
    public function testJoinValues(mixed $value, string $key, string $glue, string $expected): void {
        $this->assertEquals($expected, Strings::joinValues($value, $key, $glue));
    }

    public static function providerJoinValues(): array {
        return [
            "basic_join"           => [ [[ "n" => 1 ], [ "n" => 2 ]], "n", ", ", "1, 2" ],
            "missing_key_entry"    => [ [[ "n" => 1 ], []], "n", ", ", "1, " ],
            "string_input"         => [ "x", "n", ", ", "x" ],
            "non_array_non_string" => [ 123, "n", ", ", "" ],
        ];
    }


    #[DataProvider("providerMerge")]
    public function testMerge(string $first, string $second, string $glue, string $expected): void {
        $this->assertEquals($expected, Strings::merge($first, $second, $glue));
    }

    public static function providerMerge(): array {
        return [
            "both_values"  => [ "A", "B", " ", "A B" ],
            "first_empty"  => [ "", "B", " ", "B" ],
            "second_empty" => [ "A", "", " ", "A" ],
            "both_empty"   => [ "", "", " ", "" ],
        ];
    }


    #[DataProvider("providerToLowerCase")]
    public function testToLowerCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toLowerCase($value));
    }

    public static function providerToLowerCase(): array {
        return [
            "hello_world" => [ "Hello World", "hello world" ],
            "mixed_case"  => [ "Mixed CASE", "mixed case" ],
            "empty"       => [ "", "" ],
            "single_char" => [ "A", "a" ],
        ];
    }


    #[DataProvider("providerLowerCaseFirst")]
    public function testLowerCaseFirst(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::lowerCaseFirst($value));
    }

    public static function providerLowerCaseFirst(): array {
        return [
            "basic"       => [ "Hello", "hello" ],
            "empty"       => [ "", "" ],
            "single_char" => [ "H", "h" ],
        ];
    }


    #[DataProvider("providerToUpperCase")]
    public function testToUpperCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toUpperCase($value));
    }

    public static function providerToUpperCase(): array {
        return [
            "hello_world" => [ "hello world", "HELLO WORLD" ],
            "mixed_case"  => [ "Mixed case", "MIXED CASE" ],
            "empty"       => [ "", "" ],
            "single_char" => [ "a", "A" ],
        ];
    }


    #[DataProvider("providerUpperCaseFirst")]
    public function testUpperCaseFirst(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::upperCaseFirst($value));
    }

    public static function providerUpperCaseFirst(): array {
        return [
            "basic"       => [ "hello", "Hello" ],
            "empty"       => [ "", "" ],
            "single_char" => [ "h", "H" ],
        ];
    }


    #[DataProvider("providerIsConstantCase")]
    public function testIsConstantCase(string $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isConstantCase($value));
    }

    public static function providerIsConstantCase(): array {
        return [
            "upper_only"      => [ "ABCDEF", true ],
            "with_underscore" => [ "ABC_DEF", true ],

            "mixed_case"      => [ "AbC_DEF", false ],
            "with_dash"       => [ "ABC-def", false ],
            "empty"           => [ "", false ],
            "numeric_only"    => [ "123", false ],
        ];
    }


    #[DataProvider("providerToConstantCase")]
    public function testToConstantCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toConstantCase($value));
    }

    public static function providerToConstantCase(): array {
        return [
            // already in constant case -> should remain unchanged
            "already_constant" => [ "SOME_CONSTANT", "SOME_CONSTANT" ],

            // converts snake_case to CONSTANT_CASE
            "snake_case" => [ "some_constant", "SOME_CONSTANT" ],

            // converts kebab-case to CONSTANT_CASE
            "kebab_case" => [ "some-constant", "SOME_CONSTANT" ],

            // converts camelCase to CONSTANT_CASE
            "camelCase" => [ "someConstant", "SOME_CONSTANT" ],

            // converts PascalCase to CONSTANT_CASE
            "pascalCase" => [ "SomeConstant", "SOME_CONSTANT" ],

            // converts various delimiters to CONSTANT_CASE
            "space_delimiter" => [ "Hello world", "HELLO_WORLD" ],
            "dot_delimiter"   => [ "hello.world", "HELLO_WORLD" ],
            "colon_delimiter" => [ "hello:world", "HELLO_WORLD" ],
            "semi_delimiter"  => [ "hello;world", "HELLO_WORLD" ],

            // edge cases
            "single_letter" => [ "A", "A" ],
            "empty"         => [ "", "" ],
        ];
    }


    #[DataProvider("providerIsSnakeCase")]
    public function testIsSnakeCase(string $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isSnakeCase($value));
    }

    public static function providerIsSnakeCase(): array {
        return [
            "valid_hello_world"  => [ "hello_world", true ],
            "valid_single_char"  => [ "a", true ],

            "invalid_uppercase"  => [ "Hello_world", false ],
            "invalid_camel_case" => [ "helloWorld", false ],
            "invalid_space"      => [ "hello world", false ],
            "invalid_dash"       => [ "hello-world", false ],
            "invalid_empty"      => [ "", false ],
        ];
    }


    #[DataProvider("providerToSnakeCase")]
    public function testToSnakeCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toSnakeCase($value));
    }

    public static function providerToSnakeCase(): array {
        return [
            // already in snake_case -> should remain unchanged
            "already_snake" => [ "some_hey", "some_hey" ],

            // converts CONSTANT_CASE to snake_case
            "constant_case" => [ "SOME_HEY", "some_hey" ],

            // converts kebab-case to snake_case
            "kebab_case" => [ "some-hey", "some_hey" ],

            // converts camelCase to snake_case
            "camel_case" => [ "someHey", "some_hey" ],

            // converts PascalCase to snake_case
            "pascal_case" => [ "SomeHey", "some_hey" ],
            "pascal_with_acronym_mid" => [ "SomeHEYData", "some_hey_data" ],
            "pascal_with_acronym_start" => [ "HEYSomeData", "hey_some_data" ],

            // converts various delimiters to snake_case
            "space_delimiter" => [ "Hello world", "hello_world" ],
            "dash_delimiter" => [ "hello-world", "hello_world" ],
            "dot_delimiter" => [ "hello.world", "hello_world" ],
            "colon_delimiter" => [ "hello:world", "hello_world" ],
            "semi_delimiter" => [ "hello;world", "hello_world" ],

            // edge cases
            "single_letter" => [ "A", "a" ],
            "empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerIsKebabCase")]
    public function testIsKebabCase(string $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isKebabCase($value));
    }

    public static function providerIsKebabCase(): array {
        return [
            "valid_hello_world"  => [ "hello-world", true ],
            "valid_single_char"  => [ "a", true ],

            "invalid_uppercase"  => [ "Hello-world", false ],
            "invalid_camel_case" => [ "helloWorld", false ],
            "invalid_space"      => [ "hello world", false ],
            "invalid_snake_case" => [ "hello_world", false ],
            "invalid_empty"      => [ "", false ],
        ];
    }


    #[DataProvider("providerToKebabCase")]
    public function testToKebabCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toKebabCase($value));
    }

    public static function providerToKebabCase(): array {
        return [
            // already in kebab-case -> should remain unchanged
            "already_kebab" => [ "some-hey", "some-hey" ],

            // converts CONSTANT_CASE to kebab-case
            "constant_case" => [ "SOME_HEY", "some-hey" ],

            // converts snake_case to kebab-case
            "snake_case" => [ "some_hey", "some-hey" ],

            // converts camelCase to kebab-case
            "camel_case" => [ "someHey", "some-hey" ],

            // converts PascalCase to kebab-case
            "pascal_case" => [ "SomeHey", "some-hey" ],
            "pascal_with_acronym_mid" => [ "SomeHEYData", "some-hey-data" ],
            "pascal_with_acronym_start" => [ "HEYSomeData", "hey-some-data" ],

            // converts various delimiters to kebab-case
            "space_delimiter" => [ "Hello world", "hello-world" ],
            "dot_delimiter" => [ "hello.world", "hello-world" ],
            "colon_delimiter" => [ "hello:world", "hello-world" ],
            "semi_delimiter" => [ "hello;world", "hello-world" ],

            // edge cases
            "single_letter" => [ "A", "a" ],
            "empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerIsPascalCase")]
    public function testIsPascalCase(string $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isPascalCase($value));
    }

    public static function providerIsPascalCase(): array {
        return [
            "hello_world"         => [ "HelloWorld", true ],
            "ab"                  => [ "Ab", true ],
            "pascal_with_acronym" => [ "SomeHEYData", true ],
            "acronym_prefix"      => [ "HEYSomeData", true ],

            "camel_case_invalid"  => [ "helloWorld", false ],
            "space_invalid"       => [ "Hello World", false ],
            "dash_invalid"        => [ "Hello-World", false ],
            "empty_invalid"       => [ "", false ],
        ];
    }


    #[DataProvider("providerToPascalCase")]
    public function testToPascalCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toPascalCase($value));
    }

    public static function providerToPascalCase(): array {
        return [
            // already in PascalCase -> should remain unchanged
            "already_pascal" => [ "HelloWorld", "HelloWorld" ],

            // converts CONSTANT_CASE to PascalCase
            "constant_case" => [ "SOME_HEY", "SomeHey" ],

            // converts snake_case to PascalCase
            "snake_case" => [ "some_hey", "SomeHey" ],

            // converts kebab-case to PascalCase
            "kebab_case" => [ "some-hey", "SomeHey" ],

            // converts camelCase to PascalCase
            "camel_case" => [ "someHey", "SomeHey" ],
            "camel_with_acronym" => [ "someHEYData", "SomeHEYData" ],

            // converts various delimiters to PascalCase
            "single_word" => [ "hello", "Hello" ],
            "space_delimiter" => [ "Hello world", "HelloWorld" ],
            "dot_delimiter" => [ "hello.world", "HelloWorld" ],
            "colon_delimiter" => [ "hello:world", "HelloWorld" ],
            "semi_delimiter" => [ "hello;world", "HelloWorld" ],

            // edge cases
            "single_letter" => [ "a", "A" ],
            "empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerIsCamelCase")]
    public function testIsCamelCase(string $value, bool $expected): void {
        $this->assertEquals($expected, Strings::isCamelCase($value));
    }

    public static function providerIsCamelCase(): array {
        return [
            "valid_camel"       => [ "helloWorld", true ],
            "valid_single_char" => [ "a", true ],

            "invalid_pascal"    => [ "HelloWorld", false ],
            "invalid_space"     => [ "hello world", false ],
            "invalid_kebab"     => [ "hello-world", false ],
            "invalid_empty"     => [ "", false ],
        ];
    }


    #[DataProvider("providerToCamelCase")]
    public function testToCamelCase(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toCamelCase($value));
    }

    public static function providerToCamelCase(): array {
        return [
            // already in camelCase -> should remain unchanged
            "already_camel" => [ "helloWorld", "helloWorld" ],

            // converts CONSTANT_CASE to camelCase
            "constant_case" => [ "SOME_HEY", "someHey" ],

            // converts snake_case to camelCase
            "snake_case" => [ "some_hey", "someHey" ],

            // converts kebab-case to camelCase
            "kebab_case" => [ "some-hey", "someHey" ],

            // converts PascalCase to camelCase
            "pascal_case" => [ "SomeHey", "someHey" ],

            // converts various delimiters to camelCase
            "single_word" => [ "Hello", "hello" ],
            "space_delimiter" => [ "Hello world", "helloWorld" ],
            "dash_delimiter" => [ "hello-world", "helloWorld" ],
            "dot_delimiter" => [ "hello.world", "helloWorld" ],
            "colon_delimiter" => [ "hello:world", "helloWorld" ],
            "semi_delimiter" => [ "hello;world", "helloWorld" ],

            // edge cases
            "single_letter" => [ "A", "a" ],
            "empty" => [ "", "" ],
        ];
    }


    #[DataProvider("providerToHtml")]
    public function testToHtml(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::toHtml($value));
    }

    public static function providerToHtml(): array {
        return [
            "line_break"   => [ "a\nb", "a<br>b" ],
            "single_break" => [ "\n", "<br>" ],
            "plain_text"   => [ "ab", "ab" ],
            "empty"        => [ "", "" ],
        ];
    }


    #[DataProvider("providerRemoveHtml")]
    public function testRemoveHtml(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::removeHtml($value));
    }

    public static function providerRemoveHtml(): array {
        return [
            "basic"               => [ "<b>abc</b>", "abc" ],
            "empty"               => [ "", "" ],
            "style_at_start"      => [ "<style>body{}</style>abc", "abc" ],
            "style_without_end"   => [ "<style>body{}abc", "<style>body{}abc" ],
            "style_in_the_middle" => [ "pre<style>body{}</style>abc", "preabc" ],
        ];
    }


    #[DataProvider("providerDecodeHtml")]
    public function testDecodeHtml(string $value, string $expected): void {
        $this->assertEquals($expected, Strings::decodeHtml($value));
    }

    public static function providerDecodeHtml(): array {
        return [
            "ampersand_entity"     => [ "&amp;", "&" ],
            "less_than_entity"     => [ "&lt;", "<" ],
            "numeric_A"            => [ "&#65;", "A" ],
            "numeric_e_acute"      => [ "&#233;", "é" ],
            "hex_A"                => [ "&#x41;", "A" ],
            "hex_e_acute_upperhex" => [ "&#xE9;", "é" ],
        ];
    }


    #[DataProvider("providerMakeShort")]
    public function testMakeShort(string $value, int $length, string $expected, ?bool $asUtf8 = null): void {
        if ($asUtf8 === null) {
            $actual = Strings::makeShort($value, $length);
        } else {
            $actual = Strings::makeShort($value, $length, $asUtf8);
        }
        $this->assertEquals($expected, $actual);
    }

    public static function providerMakeShort(): array {
        return [
            "len0_original"   => [ "anything", 0, "anything", null ],
            "short_unchanged" => [ "short", 10, "short", null ],
            "trunc_utf8_def"  => [ "abcdef", 5, "ab...", null ],
            "nl_first_line"   => [ "first\nsecond", 10, "first", null ],
            "utf8_exact"      => [ "tést", 4, "tést", null ],
            "utf8_trunc"      => [ "tést", 2, "tés...", null ],
            "non_utf8_trunc"  => [ "abcdefgh", 5, "abcde", false ],
        ];
    }


    #[DataProvider("providerIsShort")]
    public function testIsShort(string $value, int $length, bool $expected): void {
        $this->assertEquals($expected, Strings::isShort($value, $length));
    }

    public static function providerIsShort(): array {
        return [
            "long_string"  => [ str_repeat("x", 50), 10, true ],
            "short_string" => [ "abcdefgh", 3, true ],
        ];
    }


    #[DataProvider("providerIsAlphaNum")]
    public function testIsAlphaNum(string $value, bool $allowDashUnderscore, ?int $length, bool $expected): void {
        if ($length === null) {
            $actual = Strings::isAlphaNum($value, $allowDashUnderscore);
        } else {
            $actual = Strings::isAlphaNum($value, $allowDashUnderscore, $length);
        }
        $this->assertSame($expected, $actual);
    }

    public static function providerIsAlphaNum(): array {
        return [
            "basic"      => [ "abc123", false, null, true ],
            "space"      => [ "abc 123", false, null, false ],
            "letters"    => [ "ABC", false, null, true ],
            "numbers"    => [ "123", false, null, true ],
            "dash_no"    => [ "abc-123", false, null, false ],
            "under_no"   => [ "abc_123", false, null, false ],
            "dash_yes"   => [ "abc-123", true, null, true ],
            "under_yes"  => [ "abc_123", true, null, true ],
            "mixed_ok"   => [ "a-b_c", true, null, true ],
            "len_ok"     => [ "abcd", false, 4, true ],
            "len_no"     => [ "abcd", false, 3, false ],
            "sep_len_ok" => [ "a-b_c", true, 5, true ],
            "sep_len_no" => [ "a-b_c", false, 5, false ],
            "empty"      => [ "", false, null, false ],
        ];
    }


    #[DataProvider("providerSanitize")]
    public function testSanitize(string $value, bool $lowercase, bool $anal, string $expected): void {
        $this->assertEquals($expected, Strings::sanitize($value, lowercase: $lowercase, anal: $anal));
    }

    public static function providerSanitize(): array {
        return [
            "basic_lowercase"    => [ "Hello!!", true, false, "hello" ],
            "anal_mode"          => [ "Hello World!!", true, true, "hello-world" ],
            "preserve_case"      => [ "Hello!!", false, false, "Hello" ],
            "accents_preserved"  => [ "ÁÉÍ", true, false, "áéí" ],
            "anal_accents_ascii" => [ "Olé Niño", true, true, "ole-nino" ],
            "underscore_removed" => [ "a_b c", true, false, "ab-c" ],
            "slash_removed"      => [ "a/b c", true, false, "ab-c" ],
            "collapse_spaces"    => [ "Many   Spaces   Here", true, false, "many-spaces-here" ],
        ];
    }


    #[DataProvider("providerHasEmoji")]
    public function testHasEmoji(string $value, bool $expected): void {
        $this->assertSame($expected, Strings::hasEmoji($value));
    }

    public static function providerHasEmoji(): array {
        return [
            "emoji_in_text"    => [ "hello 😄", true ],
            "single_emoji"     => [ "😄", true ],
            "flag_emoji"       => [ "Flags 🇺🇸 are cool", true ],
            "family_emoji"     => [ "Family: 👨‍👩‍👧‍👦", true ],
            "skin_tone_emoji"  => [ "Skin tone 👍🏽", true ],
            "single_skin_tone" => [ "👍🏽", true ],
            "zwj_emoji"        => [ "👩‍❤️‍👩", true ],
            "no_emoji"         => [ "no emoji here", false ],
            "empty"            => [ "", false ],
        ];
    }


    #[DataProvider("providerIsOnlyEmojis")]
    public function testIsOnlyEmojis(string $value, bool $expected): void {
        $this->assertSame($expected, Strings::isOnlyEmojis($value));
    }

    public static function providerIsOnlyEmojis(): array {
        return [
            // valid emoji-only strings
            "double_emoji"     => [ "😄😄", true ],
            "single_emoji"     => [ "😄", true ],
            "emoji_skin_tone"  => [ "👍🏽", true ],

            // invalid cases
            "text_with_emoji"  => [ "hi 😄", false ],
            "emoji_with_space" => [ "😄 😄", false ],
            "emoji_with_text"  => [ "😄a", false ],
            "empty"            => [ "", false ],
        ];
    }


    #[DataProvider("providerConvertEncoding")]
    public function testConvertEncoding(string $value, string $expected): void {
        $actual = Strings::convertEncoding($value);

        $this->assertIsString($actual);
        $this->assertEquals($expected, $actual);
    }

    public static function providerConvertEncoding(): array {
        return [
            "raw_accented_character" => [ "é", "&eacute;" ],
            "named_entity"           => [ "&eacute;", "&eacute;" ],
            "multi_character"        => [ "Olé", "Ol&eacute;" ],
            "ascii_only"             => [ "A", "A" ],
            "numeric_entity"         => [ "&#233;", "&#233;" ],
        ];
    }


    #[DataProvider("providerBase64Decode")]
    public function testBase64Decode(string $input, string $expected): void {
        $this->assertEquals($expected, Strings::base64Decode($input));
    }

    public static function providerBase64Decode(): array {
        return [
            "simple_ascii"      => [ base64_encode("hi"), "hi" ],
            "empty_input"       => [ "", "" ],
            "invalid_base64"    => [ "not-base64!!", "" ],
            "newline_invalid"   => [ base64_encode("x") . "\n", "x" ],
            "utf8_round_trip"   => [ base64_encode("tést"), "tést" ],
            "binary_round_trip" => [ base64_encode("\x00\x01\xFF"), "\x00\x01\xFF" ],
        ];
    }
}
