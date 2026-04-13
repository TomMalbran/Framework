<?php
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\System\EmailCode;
use Framework\Utils\Strings;

use PHPUnit\Framework\TestCase;
use stdClass;

class StringsTest extends TestCase {

    public function testIsString() {
        // valid strings
        $this->assertTrue(Strings::isString(""));
        $this->assertTrue(Strings::isString("x"));
        $this->assertTrue(Strings::isString("123"));

        // invalid strings
        $this->assertFalse(Strings::isString(1));
        $this->assertFalse(Strings::isString(null));
        $this->assertFalse(Strings::isString(false));
        $this->assertFalse(Strings::isString(new stdClass()));
    }

    public function testToString() {
        $this->assertEquals("x", Strings::toString("x"));
        $this->assertEquals("123", Strings::toString(123));
        $this->assertEquals("12.3", Strings::toString(12.3));
        $this->assertEquals("", Strings::toString(new stdClass()));

        // Date conversion uses ReverseSeconds format
        $d = Date::createTime(1, 1, 2020, 12, 34, 56);
        $this->assertEquals("2020-01-01 12:34:56", Strings::toString($d));

        // Enum conversion returns its string name/value
        $this->assertEquals("Test", Strings::toString(EmailCode::Test));
    }

    public function testTrim() {
        $this->assertEquals("a", Strings::trim(" a "));
        $this->assertEquals("a", Strings::trim(" a"));
        $this->assertEquals("1", Strings::trim(1));
        $this->assertEquals("1.2", Strings::trim(1.2));
        $this->assertEquals("", Strings::trim(null));
    }

    public function testNormalized() {
        $this->assertEquals("x\nline", Strings::normalized(" x\r\nline "));
        $this->assertEquals("a", Strings::normalized("a"));
        $this->assertEquals("", Strings::normalized(null));
    }

    public function testLength() {
        $this->assertEquals(4, Strings::length("tést"));
        $this->assertEquals(1, Strings::length("😄"));
        $this->assertEquals(7, Strings::length("hello 😄"));
        $this->assertEquals(0, Strings::length(""));
    }

    public function testIsEqual() {
        // default behavior: case-insensitive, trim values
        $this->assertTrue(Strings::isEqual(" A ", "a"));

        // case-sensitive (no ignore case) -> should be false
        $this->assertFalse(Strings::isEqual(" A ", "a", false));

        // no trim -> spaces are significant
        $this->assertFalse(Strings::isEqual(" A ", "a", true, false));

        // case-sensitive and no trim
        $this->assertFalse(Strings::isEqual("A ", "A", false, false));

        // numeric conversions
        $this->assertTrue(Strings::isEqual(123, " 123 "));

        // Date and Enum conversions
        $d = Date::createTime(1, 1, 2020, 12, 34, 56);
        $this->assertTrue(Strings::isEqual($d, "2020-01-01 12:34:56"));
        $this->assertTrue(Strings::isEqual(EmailCode::Test, "Test"));

        // null and booleans convert to empty string
        $this->assertTrue(Strings::isEqual(null, ""));
        $this->assertTrue(Strings::isEqual(false, ""));

        // different strings still false
        $this->assertFalse(Strings::isEqual("A", "B", false));
    }

    public function testEquals() {
        // valid cases
        $this->assertTrue(Strings::equals("", ""));
        $this->assertTrue(Strings::equals("one", "one"));
        $this->assertTrue(Strings::equals("one", "two", "one"));
        $this->assertTrue(Strings::equals("one", "one", "two"));

        // invalid cases
        $this->assertFalse(Strings::equals("", "-"));
        $this->assertFalse(Strings::equals("", "two", "three"));
        $this->assertFalse(Strings::equals("one", "two", "three"));
    }

    public function testEqualsCaseInsensitive() {
        // valid cases
        $this->assertTrue(Strings::equalsCaseInsensitive("", ""));
        $this->assertTrue(Strings::equalsCaseInsensitive("One", "oNe"));
        $this->assertTrue(Strings::equalsCaseInsensitive("one", "one", "two"));
        $this->assertTrue(Strings::equalsCaseInsensitive("One", "oNe", "two"));

        // invalid cases
        $this->assertFalse(Strings::equalsCaseInsensitive("", "-"));
        $this->assertFalse(Strings::equalsCaseInsensitive("", "two", "three"));
        $this->assertFalse(Strings::equalsCaseInsensitive("One", "Two"));
        $this->assertFalse(Strings::equalsCaseInsensitive("One", "Two", "Three"));
    }

    public function testContains() {
        // case-insensitive single needle
        $this->assertTrue(Strings::contains("Hello World", "world", true));
        // case-sensitive single needle -> not found
        $this->assertFalse(Strings::contains("Hello World", "world", false));

        // array of needles, atLeastOne = true (default) -> any match
        $this->assertTrue(Strings::contains("abc", [ "x", "b" ], false, true));
        $this->assertFalse(Strings::contains("abc", [ "x", "y" ], false, true));

        // array of needles, atLeastOne = false -> requires all needles present
        $this->assertTrue(Strings::contains("abc", [ "a", "b" ], false, false));
        $this->assertFalse(Strings::contains("abc", [ "a", "x" ], false, false));

        // case-insensitive with multiple needles
        $this->assertTrue(Strings::contains("AbCd", [ "a", "C" ], true, false));

        // single-element array behaves like single needle
        $this->assertTrue(Strings::contains("abc", [ "b" ], false, true));
    }

    public function testStartsWith() {
        $this->assertTrue(Strings::startsWith("prefix_value", "prefix"));
        $this->assertFalse(Strings::startsWith("nope", "pre"));

        // multiple needles: any match should return true
        $this->assertTrue(Strings::startsWith("prefix_value", "no", "prefix"));
        // when none match, return false
        $this->assertFalse(Strings::startsWith("nope", "pre", "xx"));
    }

    public function testStartsWithCaseInsensitive() {
        $this->assertTrue(Strings::startsWithCaseInsensitive("AbC", "a"));

        // multiple needles case-insensitive
        $this->assertTrue(Strings::startsWithCaseInsensitive("AbC", "x", "A"));
        $this->assertFalse(Strings::startsWithCaseInsensitive("AbC", "x", "y"));
    }

    public function testEndsWith() {
        $this->assertTrue(Strings::endsWith("file.php", ".php"));
        $this->assertFalse(Strings::endsWith("file.txt", ".php"));

        // multiple needles - any matching needle should return true
        $this->assertTrue(Strings::endsWith("index.html", ".php", ".html"));

        // case-sensitive: uppercase extension won't match lowercase needle
        $this->assertFalse(Strings::endsWith("readme.MD", ".md", ".txt"));
        $this->assertTrue(Strings::endsWith("readme.md", ".md", ".txt"));
        $this->assertFalse(Strings::endsWith("noext", ".php", ".html"));
    }

    public function testEndsWithCaseInsensitive() {
        $this->assertTrue(Strings::endsWithCaseInsensitive("AbC", "c"));

        // multiple needles case-insensitive
        $this->assertTrue(Strings::endsWithCaseInsensitive("readme.MD", ".md", ".txt"));
        $this->assertTrue(Strings::endsWithCaseInsensitive("index.HTML", ".php", ".html"));
        $this->assertFalse(Strings::endsWithCaseInsensitive("file", ".php", ".txt"));
    }

    public function testMatch() {
        // anchored numeric match
        $this->assertTrue(Strings::match("123", "/^[0-9]+$/"));
        $this->assertFalse(Strings::match("a1", "/^[0-9]+$/"));

        // unanchored digit search
        $this->assertTrue(Strings::match("abc123", "/\\d+/"));

        // case-insensitive flag
        $this->assertTrue(Strings::match("HELLO", "/hello/i"));

        // pattern that matches nothing -> false
        $this->assertFalse(Strings::match("b", "/^a$/"));

        // invalid regexp should result in false; suppress PHP warnings during the call
        $this->assertFalse(Strings::match("anything", "invalid"));
        $this->assertFalse(Strings::match("anything", "/]invalid/"));
    }

    public function testGetAllMatches() {
        // simple digit matches
        $this->assertEquals([ "1", "22" ], Strings::getAllMatches("a1b22", "/\\d+/"));
        $this->assertEquals([], Strings::getAllMatches("abc", "/\\d+/"));

        // pattern with groups: returned list merges full matches then each group's matches
        $this->assertEquals([
            "abb", "ab", // full matches
            "a", "a",    // group 1 matches
            "bb", "b"    // group 2 matches
        ], Strings::getAllMatches("abbab", "/(a)(b+)/"));

        // single capturing group duplicates entries when merged
        $this->assertEquals([
            "1", "22",    // full matches
            "1", "22"     // group matches
        ], Strings::getAllMatches("a1b22", "/(\\d+)/"));

        // invalid regexp should return empty array (function guards against false)
        $this->assertEquals([], Strings::getAllMatches("anything", "/]invalid/"));
    }

    public function testOnlyOneCharacter() {
        // valid cases
        $this->assertTrue(Strings::onlyOneCharacter("aaa", "a"));

        // invalid cases
        $this->assertFalse(Strings::onlyOneCharacter("", "a"));
        $this->assertFalse(Strings::onlyOneCharacter("aaa", "aa"));
        $this->assertFalse(Strings::onlyOneCharacter("aba", "a"));
        $this->assertFalse(Strings::onlyOneCharacter("a b a", "a"));
    }

    public function testCompare() {
        // basic ordering
        $this->assertGreaterThan(0, Strings::compare("b", "a"));
        $this->assertLessThan(0, Strings::compare("a", "b"));

        // equal strings -> zero
        $this->assertEquals(0, Strings::compare("same", "same"));

        // reverse ordering when orderAsc = false
        $this->assertLessThan(0, Strings::compare("b", "a", false));
        $this->assertGreaterThan(0, Strings::compare("a", "b", false));

        // case-insensitive comparisons
        $this->assertLessThan(0, Strings::compare("a", "B", true, true));
        $this->assertGreaterThan(0, Strings::compare("B", "a", true, true));

        // combination: orderAsc = false with case-insensitive
        $this->assertGreaterThan(0, Strings::compare("a", "B", false, true));
        $this->assertLessThan(0, Strings::compare("B", "a", false, true));
    }

    public function testGetLetter() {
        // valid indices in uppercase
        $this->assertEquals("A", Strings::getLetter(0));
        $this->assertEquals("C", Strings::getLetter(2));

        // valid indices in lowercase
        $this->assertEquals("a", Strings::getLetter(0, false));
        $this->assertEquals("c", Strings::getLetter(2, false));

        // invalid indices should return empty string
        $this->assertEquals("", Strings::getLetter(200));
        $this->assertEquals("", Strings::getLetter(-1));
    }

    public function testGetNumber() {
        // valid letters
        $this->assertEquals(1, Strings::getNumber("A"));
        $this->assertEquals(3, Strings::getNumber("c"));

        // invalid letters should return 0
        $this->assertEquals(0, Strings::getNumber("-"));
        $this->assertEquals(0, Strings::getNumber("AA"));
        $this->assertEquals(0, Strings::getNumber("cc"));
    }

    public function testRepeat() {
        // valid cases
        $this->assertEquals("xxx", Strings::repeat("x", 3));
        $this->assertEquals("xyxyxy", Strings::repeat("xy", 3));

        // zero or negative counts should return empty string
        $this->assertEquals("", Strings::repeat("x", 0));
        $this->assertEquals("", Strings::repeat("x", -1));
    }

    public function testRandom() {
        $r = Strings::random(5);
        $this->assertIsString($r);
        $this->assertEquals(5, strlen($r));
    }

    public function testRandomChar() {
        $this->assertEquals("", Strings::randomChar(""));
        $this->assertEquals("a", Strings::randomChar("a"));
        $this->assertContains(Strings::randomChar("abc"), str_split("abc"));
    }

    public function testRandomCode() {
        $code = Strings::randomCode(6, "ld");
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));

        // test using default values
        $c = Strings::randomCode();
        $this->assertIsString($c);
        $this->assertEquals(8, strlen($c));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $c);

        // 'a' -> both lower and upper letters
        $c = Strings::randomCode(10, "a");
        $this->assertMatchesRegularExpression('/^[a-zA-Z]+$/', $c);

        // 'l' -> lowercase only
        $c = Strings::randomCode(8, "l");
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $c);

        // 'u' -> uppercase only
        $c = Strings::randomCode(8, "u");
        $this->assertMatchesRegularExpression('/^[A-Z]+$/', $c);

        // 'd' -> digits only
        $c = Strings::randomCode(8, "d");
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $c);

        // 's' -> symbols set !@#$%&*?
        $c = Strings::randomCode(8, "s");
        $this->assertMatchesRegularExpression('/^[!@#\$%&\*\?]+$/', $c);

        // combinations (ensure allowed chars are subset)
        $c = Strings::randomCode(12, "ld");
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/i', $c);

        // test empty set -> should return empty string
        $c = Strings::randomCode(8, "");
        $this->assertEquals("", $c);

        // test invalid set -> should return empty string
        $c = Strings::randomCode(8, "x");
        $this->assertEquals("", $c);
    }

    public function testToNumber() {
        $this->assertEquals("12", Strings::toNumber("a1b2"));
        $this->assertEquals("", Strings::toNumber("abc"));
    }

    public function testReplace() {
        // simple scalar replacement
        $this->assertEquals("bar", Strings::replace("foo", "foo", "bar"));
        $this->assertEquals("bar bar", Strings::replace("foo foo", "foo", "bar"));

        // mapping replacement: keys replaced by their values
        $this->assertEquals("one:two", Strings::replace("a:b", [ "a" => "one", "b" => "two" ]));
        // mapping with no matches should return original
        $this->assertEquals("abc", Strings::replace("abc", [ "x" => "y" ]));

        // array search with single replacement string -> all occurrences replaced by same value
        $this->assertEquals("XXc", Strings::replace("abc", [ "a", "b" ], "X"));

        // array search with array replacement
        $this->assertEquals("xyc", Strings::replace("abc", [ "a", "b" ], [ "x", "y" ]));

        // null replace
        $this->assertEquals("", Strings::replace("abc", "a", null));
    }

    public function testReplaceStart() {
        // basic prefix replacement
        $this->assertEquals("barBAR", Strings::replaceStart("fooBAR", "foo", "bar"));

        // no-op when prefix not present
        $this->assertEquals("fooBAR", Strings::replaceStart("fooBAR", "no", "x"));

        // full-string prefix replacement
        $this->assertEquals("bar", Strings::replaceStart("foo", "foo", "bar"));

        // empty input remains empty
        $this->assertEquals("", Strings::replaceStart("", "foo", "bar"));

        // case-sensitive behavior: only exact match replaced
        $this->assertEquals("barBAR", Strings::replaceStart("FooBAR", "Foo", "bar"));
        $this->assertEquals("fooBAR", Strings::replaceStart("fooBAR", "Foo", "bar"));
    }

    public function testReplaceEnd() {
        // basic suffix replacement
        $this->assertEquals("fooBAZ", Strings::replaceEnd("fooBAR", "BAR", "BAZ"));

        // no-op when suffix not present
        $this->assertEquals("fooBAR", Strings::replaceEnd("fooBAR", "no", "x"));

        // full-string suffix replacement
        $this->assertEquals("baz", Strings::replaceEnd("bar", "bar", "baz"));

        // empty input remains empty
        $this->assertEquals("", Strings::replaceEnd("", "x", "y"));

        // case-sensitive behavior: only exact match replaced
        $this->assertEquals("fooBAZ", Strings::replaceEnd("fooBar", "Bar", "BAZ"));
        $this->assertEquals("fooBAR", Strings::replaceEnd("fooBAR", "Bar", "BAZ"));
    }

    public function testReplacePattern() {
        // simple vowel replacements
        $this->assertEquals("h_ll_", Strings::replacePattern("hello", "/[eo]/", "_"));

        // replace digit groups with a single marker
        $this->assertEquals("aNbN", Strings::replacePattern("a1b22", "/\\d+/", "N"));

        // limit parameter: only first match replaced
        $this->assertEquals("aNb22", Strings::replacePattern("a1b22", "/\\d+/", "N", 1));

        // capturing group replacement using $1 backreference
        $this->assertEquals("a[1]b[2]", Strings::replacePattern("a1b2", "/(\\d)/", "[$1]"));

        // case-insensitive replacement
        $this->assertEquals("Jello", Strings::replacePattern("hello", "/h/i", "J"));

        // array of patterns with array of replacements
        $this->assertEquals("h_ll_N", Strings::replacePattern("hello123", [ "/[eo]/", "/\\d+/" ], [ "_", "N" ]));
    }

    public function testReplaceCallback() {
        // basic callback wrapping matches
        $cb = Strings::replaceCallback("a1b2", "/(\\d+)/", function($m) { return "[" . $m[0] . "]"; });
        $this->assertEquals("a[1]b[2]", $cb);

        // limit parameter: only the first match replaced
        $cb = Strings::replaceCallback("a1b22", "/(\\d+)/", function($m) { return "[" . $m[0] . "]"; }, 1);
        $this->assertEquals("a[1]b22", $cb);

        // callback can transform the match (double numeric values)
        $cb = Strings::replaceCallback("a2b3", "/(\\d+)/", function($m) { return (string)((int)$m[0] * 2); });
        $this->assertEquals("a4b6", $cb);

        // array of patterns: callback is invoked for each pattern's matches
        $cb = Strings::replaceCallback("a1e2", ["/[ae]/", "/(\\d)/"], function($m) {
            if (ctype_digit($m[0])) {
                return "{" . $m[0] . "}";
            }
            return strtoupper($m[0]);
        });
        $this->assertEquals("A{1}E{2}", $cb);
    }

    public function testStripStart() {
        // basic start removal
        $this->assertEquals("value", Strings::stripStart("pre_value", "pre_"));

        // multiple needles: first matching needle is removed
        $this->assertEquals("value", Strings::stripStart("prefix_value", "prefix_", "pre"));

        // when none of the needles match, original returned
        $this->assertEquals("prefix_value", Strings::stripStart("prefix_value", "x", "y"));

        // empty input stays empty
        $this->assertEquals("", Strings::stripStart("", "pre"));

        // ordering matters: earlier needle can remove a smaller piece
        $this->assertEquals("fix_value", Strings::stripStart("prefix_value", "pre", "prefix_"));
    }

    public function testStripEnd() {
        // basic end removal
        $this->assertEquals("pre", Strings::stripEnd("pre_suf", "_suf"));

        // multiple needles: any matching suffix removed
        $this->assertEquals("file", Strings::stripEnd("file.php", ".php", ".txt"));

        // when none match, original returned
        $this->assertEquals("file.php", Strings::stripEnd("file.php", ".x", ".y"));

        // empty input stays empty
        $this->assertEquals("", Strings::stripEnd("", "x"));

        // ordering: shorter suffix first will remove only that suffix when it matches
        $this->assertEquals("file.tx", Strings::stripEnd("file.txt", "t", ".txt"));
    }

    public function testStripStartEnd() {
        // basic start+end removal
        $this->assertEquals("mid", Strings::stripStartEnd("[mid]", "[", "]"));

        // longer delimiters
        $this->assertEquals("text", Strings::stripStartEnd("<<text>>", "<<", ">>"));

        // empty input stays empty
        $this->assertEquals("", Strings::stripStartEnd("", "[", "]"));

        // only start matches -> end unchanged
        $this->assertEquals("foo", Strings::stripStartEnd("pre_foo", "pre_", "]"));

        // only end matches -> start unchanged
        $this->assertEquals("bar", Strings::stripStartEnd("bar_suf", "[", "_suf"));
    }

    public function testPadLeft() {
        // basic numeric padding on the left
        $this->assertEquals("001", Strings::padLeft("1", 3, "0"));

        // when length is less or equal to string length, original returned
        $this->assertEquals("abcd", Strings::padLeft("abcd", 3, "0"));
        $this->assertEquals("abcd", Strings::padLeft("abcd", 4, "0"));

        // multi-character needle is repeated as needed
        $this->assertEquals("abab1", Strings::padLeft("1", 5, "ab"));

        // padding with spaces (default)
        $this->assertEquals("  x", Strings::padLeft("x", 3));
    }

    public function testPadRight() {
        // basic padding on the right
        $this->assertEquals("1  ", Strings::padRight("1", 3, " "));

        // when length is less or equal to string length, original returned
        $this->assertEquals("hello", Strings::padRight("hello", 3, " "));
        $this->assertEquals("hello", Strings::padRight("hello", 5, " "));

        // multi-character needle is repeated and truncated as needed
        $this->assertEquals("1xyx", Strings::padRight("1", 4, "xy"));

        // default needle (space)
        $this->assertEquals("x  ", Strings::padRight("x", 3));
    }

    public function testAddPrefix() {
        // adds missing prefix
        $this->assertEquals("pre_x", Strings::addPrefix("x", "pre_"));

        // does not duplicate when prefix already present
        $this->assertEquals("pre_x", Strings::addPrefix("pre_x", "pre_"));

        // multi-character prefix
        $this->assertEquals("Mr x", Strings::addPrefix("x", "Mr "));
    }

    public function testAddSuffix() {
        // adds missing suffix
        $this->assertEquals("x_suf", Strings::addSuffix("x", "_suf"));

        // does not duplicate when suffix already present
        $this->assertEquals("x_suf", Strings::addSuffix("x_suf", "_suf"));

        // multi-character suffix
        $this->assertEquals("x Jr.", Strings::addSuffix("x", " Jr."));
    }

    public function testAddPrefixSuffix() {
        // adds both prefix and suffix when missing
        $this->assertEquals("pre_x_suf", Strings::addPrefixSuffix("x", "pre_", "_suf"));

        // when prefix present but suffix missing
        $this->assertEquals("pre_x_suf", Strings::addPrefixSuffix("pre_x", "pre_", "_suf"));

        // when suffix present but prefix missing
        $this->assertEquals("pre_x_suf", Strings::addPrefixSuffix("x_suf", "pre_", "_suf"));

        // when both present -> unchanged
        $this->assertEquals("pre_x_suf", Strings::addPrefixSuffix("pre_x_suf", "pre_", "_suf"));
    }

    public function testSubstring() {
        // basic substring with length
        $this->assertEquals("bcd", Strings::substring("abcdef", 1, 3));

        // substring without length returns rest of string
        $this->assertEquals("bcdef", Strings::substring("abcdef", 1));

        // negative start counts from end
        $this->assertEquals("def", Strings::substring("abcdef", -3));

        // utf8-aware substring
        $this->assertEquals("és", Strings::substring("tést", 1, 2, true));
    }

    public function testSubstringAfter() {
        // substringAfter uses last occurrence by default
        $this->assertEquals("c", Strings::substringAfter("a.b.c", "."));

        // when useFirst = true, returns after first occurrence
        $this->assertEquals("b.c", Strings::substringAfter("a.b.c", ".", true));

        // needle at end returns empty string
        $this->assertEquals("", Strings::substringAfter("a.", "."));

        // needle not found -> returns original
        $this->assertEquals("abc", Strings::substringAfter("abc", "."));

        // empty needle behaves as position 0 -> returns original
        $this->assertEquals("abc", Strings::substringAfter("abc", "", true));
    }

    public function testSubstringBefore() {
        // substringBefore uses first occurrence by default
        $this->assertEquals("a", Strings::substringBefore("a.b.c", "."));

        // when useFirst = false, returns before last occurrence
        $this->assertEquals("a.b", Strings::substringBefore("a.b.c", ".", false));

        // needle not found -> returns original
        $this->assertEquals("abc", Strings::substringBefore("abc", "."));

        // needle at start (position 0) returns original
        $this->assertEquals(".a", Strings::substringBefore(".a", "."));

        // empty needle behaves like not-found for substringBefore -> original
        $this->assertEquals("abc", Strings::substringBefore("abc", "", true));
    }

    public function testSubstringBetween() {
        // basic between
        $this->assertEquals("mid", Strings::substringBetween("x[start]mid[end]y", "[start]", "[end]"));

        // longer delimiters
        $this->assertEquals("text", Strings::substringBetween("<<text>>", "<<", ">>"));

        // missing delimiters -> original returned
        $this->assertEquals("nope", Strings::substringBetween("nope", "[", "]"));

        // empty string handled
        $this->assertEquals("", Strings::substringBetween("", "[", "]"));
    }

    public function testSplit() {
        // basic split with trim and skipEmpty
        $this->assertEquals([ "a", "b" ], Strings::split("a,,b", ",", trim: true, skipEmpty: true));

        // empty string returns empty array
        $this->assertEquals([], Strings::split("", ","));

        // empty needle returns empty array
        $this->assertEquals([], Strings::split("abc", ""));

        // passing an array returns it unchanged
        $this->assertEquals([ "x", "y" ], Strings::split([ "x", "y" ], ","));

        // when needle not present, original returned as single element
        $this->assertEquals([ "abc" ], Strings::split("abc", "|"));

        // trim and skipEmpty interactions
        $raw = " a , , b ";
        // no trim, no skipEmpty -> keeps empty and whitespace
        $this->assertEquals([ " a ", " ", " b " ], Strings::split($raw, ",", trim: false, skipEmpty: false));
        // trim, no skipEmpty -> keeps empty but trims entries
        $this->assertEquals([ "a", "", "b" ], Strings::split($raw, ",", trim: true, skipEmpty: false));
        // no trim, skipEmpty -> removes empty but keeps whitespace
        $this->assertEquals([ " a ", "b " ], Strings::split($raw, ", ", trim: false, skipEmpty: true));
        // trim and skipEmpty -> trims and removes empty
        $this->assertEquals([ "a", "b" ], Strings::split($raw, ",", trim: true, skipEmpty: true));

        // multi-character needle
        $this->assertEquals([ "a", "b", "c" ], Strings::split("a--b--c", "--"));

        // trailing separators: last empty element behavior
        $this->assertEquals([ "a", "b", "" ], Strings::split("a,b,", ",", trim: false, skipEmpty: false));
        $this->assertEquals([ "a", "b" ], Strings::split("a,b,", ",", trim: false, skipEmpty: true));

        // needle equals full string -> two empty parts
        $this->assertEquals([ "", "" ], Strings::split(",", ",", trim: false, skipEmpty: false));
        $this->assertEquals([], Strings::split(",", ",", trim: false, skipEmpty: true));
    }

    public function testSplitToWords() {
        $words = Strings::splitToWords("Hello, world!");
        $this->assertContains("Hello", $words);
        $this->assertContains("world", $words);

        // preserves words separated by punctuation
        $words = Strings::splitToWords("Wait... what?");
        $this->assertContains("Wait", $words);
        $this->assertContains("what", $words);

        // non-word characters only -> empty array
        $this->assertEquals([], Strings::splitToWords(""));
    }

    public function testJoin() {
        $this->assertEquals("a, b", Strings::join([ "a", "b" ], ", "));

        // join without glue
        $this->assertEquals("ab", Strings::join([ "a", "b" ]));

        // join with withoutEmpty flag removes empty entries
        $this->assertEquals("a,b", Strings::join([ "a", "", "b" ], ",", withoutEmpty: true));

        // numeric array is joined as strings
        $this->assertEquals("1, 2", Strings::join([ 1, 2 ], ", "));
        $this->assertEquals("1, 2", Strings::join([ 1, 0, 2 ], ", ", withoutEmpty: true));
        $this->assertEquals("1.2, 2.3", Strings::join([ 1.2, 2.3 ], ", "));

        // non-array input returns string or empty
        $this->assertEquals("x", Strings::join("x"));
        $this->assertEquals("", Strings::join(123));
    }

    public function testJoinKeys() {
        $this->assertEquals("ab", Strings::joinKeys([ "a" => 1, "b" => 2 ]));

        // array as list joins numeric keys
        $this->assertEquals("01", Strings::joinKeys([ 1, 2 ]));

        // non-array input returns string or empty
        $this->assertEquals("x", Strings::joinKeys("x"));
        $this->assertEquals("", Strings::joinKeys(123));
    }

    public function testJoinValues() {
        $this->assertEquals("1, 2", Strings::joinValues([[ "n" => 1 ], [ "n" => 2 ]], "n", ", "));

        // missing key returns empty string for that entry
        $this->assertEquals("1, ", Strings::joinValues([[ "n" => 1 ], []], "n", ", "));

        // non-array input returns string when provided
        $this->assertEquals("x", Strings::joinValues("x", "n", ", "));
        $this->assertEquals("", Strings::joinValues(123, "n", ", "));
    }

    public function testMerge() {
        $this->assertEquals("A B", Strings::merge("A", "B", " "));

        // when first empty
        $this->assertEquals("B", Strings::merge("", "B", " "));

        // when second empty
        $this->assertEquals("A", Strings::merge("A", "", " "));

        // when both empty
        $this->assertEquals("", Strings::merge("", "", " "));
    }

    public function testToLowerCase() {
        $this->assertEquals("hello world", Strings::toLowerCase("Hello World"));
        $this->assertEquals("mixed case", Strings::toLowerCase("Mixed CASE"));
        $this->assertEquals("", Strings::toLowerCase(""));
        $this->assertEquals("a", Strings::toLowerCase("A"));
    }

    public function testLowerCaseFirst() {
        $this->assertEquals("hello", Strings::lowerCaseFirst("Hello"));
        $this->assertEquals("", Strings::lowerCaseFirst(""));
        $this->assertEquals("h", Strings::lowerCaseFirst("H"));
    }

    public function testToUpperCase() {
        $this->assertEquals("HELLO WORLD", Strings::toUpperCase("hello world"));
        $this->assertEquals("MIXED CASE", Strings::toUpperCase("Mixed case"));

        // edge cases
        $this->assertEquals("", Strings::toUpperCase(""));
        $this->assertEquals("A", Strings::toUpperCase("a"));
    }

    public function testUpperCaseFirst() {
        $this->assertEquals("Hello", Strings::upperCaseFirst("hello"));
        $this->assertEquals("", Strings::upperCaseFirst(""));
        $this->assertEquals("H", Strings::upperCaseFirst("h"));
    }

    public function testIsConstantCase() {
        $this->assertTrue(Strings::isConstantCase("ABCDEF"));
        $this->assertTrue(Strings::isConstantCase("ABC_DEF"));

        // invalid cases
        $this->assertFalse(Strings::isConstantCase("AbC_DEF"));
        $this->assertFalse(Strings::isConstantCase("ABC-def"));
        $this->assertFalse(Strings::isConstantCase(""));
        $this->assertFalse(Strings::isConstantCase("123"));
    }

    public function testToConstantCase() {
        // already in constant case -> should remain unchanged
        $this->assertEquals("SOME_CONSTANT", Strings::toConstantCase("SOME_CONSTANT"));

        // converts snake_case to CONSTANT_CASE
        $this->assertEquals("SOME_CONSTANT", Strings::toConstantCase("some_constant"));

        // converts kebab-case to CONSTANT_CASE
        $this->assertEquals("SOME_CONSTANT", Strings::toConstantCase("some-constant"));

        // converts camelCase to CONSTANT_CASE
        $this->assertEquals("SOME_CONSTANT", Strings::toConstantCase("someConstant"));

        // converts PascalCase to CONSTANT_CASE
        $this->assertEquals("SOME_CONSTANT", Strings::toConstantCase("SomeConstant"));

        // converts various delimiters to CONSTANT_CASE
        $this->assertEquals("HELLO_WORLD", Strings::toConstantCase("Hello world"));
        $this->assertEquals("HELLO_WORLD", Strings::toConstantCase("hello.world"));
        $this->assertEquals("HELLO_WORLD", Strings::toConstantCase("hello:world"));
        $this->assertEquals("HELLO_WORLD", Strings::toConstantCase("hello;world"));

        // edge cases
        $this->assertEquals("A", Strings::toConstantCase("A"));
        $this->assertEquals("", Strings::toConstantCase(""));
    }

    public function testIsSnakeCase() {
        $this->assertTrue(Strings::isSnakeCase("hello_world"));
        $this->assertTrue(Strings::isSnakeCase("a"));

        // invalid cases
        $this->assertFalse(Strings::isSnakeCase("Hello_world"));
        $this->assertFalse(Strings::isSnakeCase("helloWorld"));
        $this->assertFalse(Strings::isSnakeCase("hello world"));
        $this->assertFalse(Strings::isSnakeCase("hello-world"));
        $this->assertFalse(Strings::isSnakeCase(""));
    }

    public function testToSnakeCase() {
        // already in snake_case -> should remain unchanged
        $this->assertEquals("some_hey", Strings::toSnakeCase("some_hey"));

        // converts CONSTANT_CASE to snake_case
        $this->assertEquals("some_hey", Strings::toSnakeCase("SOME_HEY"));

        // converts kebab-case to snake_case
        $this->assertEquals("some_hey", Strings::toSnakeCase("some-hey"));

        // converts camelCase to snake_case
        $this->assertEquals("some_hey", Strings::toSnakeCase("someHey"));

        // converts PascalCase to snake_case
        $this->assertEquals("some_hey", Strings::toSnakeCase("SomeHey"));
        $this->assertEquals("some_hey_data", Strings::toSnakeCase("SomeHEYData"));
        $this->assertEquals("hey_some_data", Strings::toSnakeCase("HEYSomeData"));

        // converts various delimiters to snake_case
        $this->assertEquals("hello_world", Strings::toSnakeCase("Hello world"));
        $this->assertEquals("hello_world", Strings::toSnakeCase("hello-world"));
        $this->assertEquals("hello_world", Strings::toSnakeCase("hello.world"));
        $this->assertEquals("hello_world", Strings::toSnakeCase("hello:world"));
        $this->assertEquals("hello_world", Strings::toSnakeCase("hello;world"));

        // edge cases
        $this->assertEquals("a", Strings::toSnakeCase("A"));
        $this->assertEquals("", Strings::toSnakeCase(""));
    }

    public function testIsKebabCase() {
        $this->assertTrue(Strings::isKebabCase("hello-world"));
        $this->assertTrue(Strings::isKebabCase("a"));

        // invalid cases
        $this->assertFalse(Strings::isKebabCase("Hello-world"));
        $this->assertFalse(Strings::isKebabCase("helloWorld"));
        $this->assertFalse(Strings::isKebabCase("hello world"));
        $this->assertFalse(Strings::isKebabCase("hello_world"));
        $this->assertFalse(Strings::isKebabCase(""));
    }

    public function testToKebabCase() {
        // already in kebab-case -> should remain unchanged
        $this->assertEquals("some-hey", Strings::toKebabCase("some-hey"));

        // converts CONSTANT_CASE to kebab-case
        $this->assertEquals("some-hey", Strings::toKebabCase("SOME_HEY"));

        // converts snake_case to kebab-case
        $this->assertEquals("some-hey", Strings::toKebabCase("some_hey"));

        // converts camelCase to kebab-case
        $this->assertEquals("some-hey", Strings::toKebabCase("someHey"));

        // converts PascalCase to kebab-case
        $this->assertEquals("some-hey", Strings::toKebabCase("SomeHey"));
        $this->assertEquals("some-hey-data", Strings::toKebabCase("SomeHEYData"));
        $this->assertEquals("hey-some-data", Strings::toKebabCase("HEYSomeData"));

        // converts various delimiters to kebab-case
        $this->assertEquals("hello-world", Strings::toKebabCase("Hello world"));
        $this->assertEquals("hello-world", Strings::toKebabCase("hello.world"));
        $this->assertEquals("hello-world", Strings::toKebabCase("hello:world"));
        $this->assertEquals("hello-world", Strings::toKebabCase("hello;world"));

        // edge cases
        $this->assertEquals("a", Strings::toKebabCase("A"));
        $this->assertEquals("", Strings::toKebabCase(""));
    }

    public function testIsPascalCase() {
        $this->assertTrue(Strings::isPascalCase("HelloWorld"));
        $this->assertTrue(Strings::isPascalCase("Ab"));
        $this->assertTrue(Strings::isPascalCase("SomeHEYData"));
        $this->assertTrue(Strings::isPascalCase("HEYSomeData"));

        // invalid cases
        $this->assertFalse(Strings::isPascalCase("helloWorld"));
        $this->assertFalse(Strings::isPascalCase("Hello World"));
        $this->assertFalse(Strings::isPascalCase("Hello-World"));
        $this->assertFalse(Strings::isPascalCase("Hello-World"));
        $this->assertFalse(Strings::isPascalCase(""));
    }

    public function testToPascalCase() {
        // already in PascalCase -> should remain unchanged
        $this->assertEquals("HelloWorld", Strings::toPascalCase("HelloWorld"));

        // converts CONSTANT_CASE to PascalCase
        $this->assertEquals("SomeHey", Strings::toPascalCase("SOME_HEY"));

        // converts snake_case to PascalCase
        $this->assertEquals("SomeHey", Strings::toPascalCase("some_hey"));

        // converts kebab-case to PascalCase
        $this->assertEquals("SomeHey", Strings::toPascalCase("some-hey"));

        // converts camelCase to PascalCase
        $this->assertEquals("SomeHey", Strings::toPascalCase("someHey"));
        $this->assertEquals("SomeHEYData", Strings::toPascalCase("someHEYData"));

        // converts various delimiters to PascalCase
        $this->assertEquals("Hello", Strings::toPascalCase("hello"));
        $this->assertEquals("HelloWorld", Strings::toPascalCase("Hello world"));
        $this->assertEquals("HelloWorld", Strings::toPascalCase("hello.world"));
        $this->assertEquals("HelloWorld", Strings::toPascalCase("hello:world"));
        $this->assertEquals("HelloWorld", Strings::toPascalCase("hello;world"));

        // edge cases
        $this->assertEquals("A", Strings::toPascalCase("a"));
        $this->assertEquals("", Strings::toPascalCase(""));
    }

    public function testIsCamelCase() {
        $this->assertTrue(Strings::isCamelCase("helloWorld"));
        $this->assertTrue(Strings::isCamelCase("a"));

        // invalid cases
        $this->assertFalse(Strings::isCamelCase("HelloWorld"));
        $this->assertFalse(Strings::isCamelCase("hello world"));
        $this->assertFalse(Strings::isCamelCase("hello-world"));
        $this->assertFalse(Strings::isCamelCase(""));
    }

    public function testToCamelCase() {
        // already in camelCase -> should remain unchanged
        $this->assertEquals("helloWorld", Strings::toCamelCase("helloWorld"));

        // converts CONSTANT_CASE to camelCase
        $this->assertEquals("someHey", Strings::toCamelCase("SOME_HEY"));

        // converts snake_case to camelCase
        $this->assertEquals("someHey", Strings::toCamelCase("some_hey"));

        // converts kebab-case to camelCase
        $this->assertEquals("someHey", Strings::toCamelCase("some-hey"));

        // converts PascalCase to camelCase
        $this->assertEquals("someHey", Strings::toCamelCase("SomeHey"));

        // converts various delimiters to camelCase
        $this->assertEquals("hello", Strings::toCamelCase("Hello"));
        $this->assertEquals("helloWorld", Strings::toCamelCase("Hello world"));
        $this->assertEquals("helloWorld", Strings::toCamelCase("hello-world"));
        $this->assertEquals("helloWorld", Strings::toCamelCase("hello.world"));
        $this->assertEquals("helloWorld", Strings::toCamelCase("hello:world"));
        $this->assertEquals("helloWorld", Strings::toCamelCase("hello;world"));

        // edge cases
        $this->assertEquals("a", Strings::toCamelCase("A"));
        $this->assertEquals("", Strings::toCamelCase(""));
    }

    public function testToHtml() {
        $this->assertEquals("a<br>b", Strings::toHtml("a\nb"));
        $this->assertEquals("<br>", Strings::toHtml("\n"));
        $this->assertEquals("ab", Strings::toHtml("ab"));
        $this->assertEquals("", Strings::toHtml(""));
    }

    public function testRemoveHtml() {
        $this->assertEquals("abc", Strings::removeHtml("<b>abc</b>"));
        $this->assertEquals("", Strings::removeHtml(""));

        // style tag at start should be removed entirely
        $this->assertEquals("abc", Strings::removeHtml("<style>body{}</style>abc"));

        // without end style tag
        $this->assertEquals("<style>body{}abc", Strings::removeHtml("<style>body{}abc"));

        // style tag in the middle should be removed and surrounding text preserved
        $this->assertEquals("preabc", Strings::removeHtml("pre<style>body{}</style>abc"));
    }

    public function testDecodeHtml() {
        $this->assertEquals("&", Strings::decodeHtml("&amp;"));
        $this->assertEquals("<", Strings::decodeHtml("&lt;"));

        // numeric entities
        $this->assertEquals("A", Strings::decodeHtml("&#65;"));
        $this->assertEquals("é", Strings::decodeHtml("&#233;"));

        // hexadecimal entities
        $this->assertEquals("A", Strings::decodeHtml("&#x41;"));
        $this->assertEquals("é", Strings::decodeHtml("&#xE9;"));
    }

    public function testMakeShort() {
        // when length is zero, original returned
        $this->assertEquals("anything", Strings::makeShort("anything", 0));

        // short string shorter than length -> unchanged (first line)
        $this->assertEquals("short", Strings::makeShort("short", 10));

        // truncation with utf8 enabled: length 5 -> 2 chars + '...'
        $this->assertEquals("ab...", Strings::makeShort("abcdef", 5));

        // newline handling: only first line considered
        $this->assertEquals("first", Strings::makeShort("first\nsecond", 10));

        // utf8 multi-byte handling: preserves characters correctly
        $this->assertEquals("tést", Strings::makeShort("tést", 4));
        $this->assertEquals("tés...", Strings::makeShort("tést", 2));

        // asUtf8 = false uses byte-length loop trimming
        $this->assertEquals("abcde", Strings::makeShort("abcdefgh", 5, false));
    }

    public function testIsShort() {
        $long = str_repeat("x", 50);
        $this->assertTrue(Strings::isShort($long, 10));
        $this->assertTrue(Strings::isShort("abcdefgh", 3));
    }

    public function testIsAlphaNum() {
        // basic alphanumeric
        $this->assertTrue(Strings::isAlphaNum("abc123"));
        $this->assertFalse(Strings::isAlphaNum("abc 123"));

        // pure letters and numbers
        $this->assertTrue(Strings::isAlphaNum("ABC"));
        $this->assertTrue(Strings::isAlphaNum("123"));

        // dashes and underscores disallowed by default
        $this->assertFalse(Strings::isAlphaNum("abc-123"));
        $this->assertFalse(Strings::isAlphaNum("abc_123"));

        // allow dashes/underscores when requested
        $this->assertTrue(Strings::isAlphaNum("abc-123", true));
        $this->assertTrue(Strings::isAlphaNum("abc_123", true));
        $this->assertTrue(Strings::isAlphaNum("a-b_c", true));

        // length parameter is checked before dash/underscore removal
        $this->assertTrue(Strings::isAlphaNum("abcd", false, 4));
        $this->assertFalse(Strings::isAlphaNum("abcd", false, 3));
        // original length includes dashes/underscores
        $this->assertTrue(Strings::isAlphaNum("a-b_c", true, 5));
        $this->assertFalse(Strings::isAlphaNum("a-b_c", false, 5));

        // empty string
        $this->assertFalse(Strings::isAlphaNum(""));
    }

    public function testSanitize() {
        // basic lowercase sanitization (punctuation removed)
        $this->assertEquals("hello", Strings::sanitize("Hello!!", lowercase: true, anal: false));

        // anal mode: accents removed and non-alnum chars dropped, spaces -> hyphen
        $this->assertEquals("hello-world", Strings::sanitize("Hello World!!", lowercase: true, anal: true));

        // preserve case when lowercase = false
        $this->assertEquals("Hello", Strings::sanitize("Hello!!", lowercase: false, anal: false));

        // accents preserved when anal = false, lowercased when requested
        $this->assertEquals("áéí", Strings::sanitize("ÁÉÍ", lowercase: true, anal: false));

        // anal mode converts accents to ASCII equivalents
        $this->assertEquals("ole-nino", Strings::sanitize("Olé Niño", lowercase: true, anal: true));

        // underscores and slashes removed before whitespace->hyphen conversion
        $this->assertEquals("ab-c", Strings::sanitize("a_b c", lowercase: true, anal: false));
        $this->assertEquals("ab-c", Strings::sanitize("a/b c", lowercase: true, anal: false));

        // multiple whitespace collapsed to single hyphen
        $this->assertEquals("many-spaces-here", Strings::sanitize("Many   Spaces   Here", lowercase: true, anal: false));
    }

    public function testHasEmoji() {
        // basic emoji presence
        $this->assertTrue(Strings::hasEmoji("hello 😄"));
        $this->assertTrue(Strings::hasEmoji("😄"));
        $this->assertTrue(Strings::hasEmoji("Flags 🇺🇸 are cool"));
        $this->assertTrue(Strings::hasEmoji("Family: 👨‍👩‍👧‍👦"));
        $this->assertTrue(Strings::hasEmoji("Skin tone 👍🏽"));
        $this->assertTrue(Strings::hasEmoji("👍🏽"));
        $this->assertTrue(Strings::hasEmoji("👩‍❤️‍👩"));

        // invalid cases: no emoji present
        $this->assertFalse(Strings::hasEmoji("no emoji here"));
        $this->assertFalse(Strings::hasEmoji(""));
    }

    public function testIsOnlyEmojis() {
        // valid emoji-only strings
        $this->assertTrue(Strings::isOnlyEmojis("😄😄"));
        $this->assertTrue(Strings::isOnlyEmojis("😄"));
        $this->assertTrue(Strings::isOnlyEmojis("👍🏽"));

        // invalid cases: any non-emoji character disqualifies
        $this->assertFalse(Strings::isOnlyEmojis("hi 😄"));
        $this->assertFalse(Strings::isOnlyEmojis("😄 😄"));
        $this->assertFalse(Strings::isOnlyEmojis("😄a"));
        $this->assertFalse(Strings::isOnlyEmojis(""));
    }

    public function testConvertEncoding() {
        // basic return type
        $this->assertIsString(Strings::convertEncoding("&eacute;"));

        // raw accented character should be converted to an HTML entity
        $this->assertEquals("&eacute;", Strings::convertEncoding("é"));

        // existing named entity preserved
        $this->assertEquals("&eacute;", Strings::convertEncoding("&eacute;"));

        // multi-character string with accents
        $this->assertEquals("Ol&eacute;", Strings::convertEncoding("Olé"));

        // ASCII-only input unchanged
        $this->assertEquals("A", Strings::convertEncoding("A"));

        // numeric entity preserved when provided
        $this->assertEquals("&#233;", Strings::convertEncoding("&#233;"));
    }

    public function testBase64Decode() {
        // simple ascii
        $this->assertEquals("hi", Strings::base64Decode(base64_encode("hi")));

        // empty input returns empty string
        $this->assertEquals("", Strings::base64Decode(""));

        // invalid base64 returns empty string (strict mode)
        $this->assertEquals("", Strings::base64Decode("not-base64!!"));

        // newlines / stray chars make it invalid in strict mode
        $this->assertEquals("x", Strings::base64Decode(base64_encode("x") . "\n"));

        // UTF-8 content round-trips
        $this->assertEquals("tést", Strings::base64Decode(base64_encode("tést")));

        // binary content round-trips
        $bin = "\x00\x01\xFF";
        $this->assertEquals($bin, Strings::base64Decode(base64_encode($bin)));
    }
}
