<?php
use Framework\Date\Date;
use Framework\Utils\Numbers;

use PHPUnit\Framework\TestCase;

class NumbersTest extends TestCase {

    public function testIsValid() {
        // valid numbers
        $this->assertTrue(Numbers::isValid(NAN));
        $this->assertTrue(Numbers::isValid(0, 0));
        $this->assertTrue(Numbers::isValid(5));
        $this->assertTrue(Numbers::isValid(5.5));
        $this->assertTrue(Numbers::isValid("5"));
        $this->assertTrue(Numbers::isValid("5.5"));

        // invalid types
        $this->assertFalse(Numbers::isValid("a"));
        $this->assertFalse(Numbers::isValid(null));
        $this->assertFalse(Numbers::isValid([]));
        $this->assertFalse(Numbers::isValid(new stdClass()));
        $this->assertFalse(Numbers::isValid(true));
        $this->assertFalse(Numbers::isValid(false));
        $this->assertFalse(Numbers::isValid(""));
        $this->assertFalse(Numbers::isValid(" "));

        // min / max checks
        $this->assertFalse(Numbers::isValid(0, 1));
        $this->assertTrue(Numbers::isValid(5, 1, 10));
        $this->assertFalse(Numbers::isValid(0, 1, 10));
        $this->assertFalse(Numbers::isValid(11, 1, 10));
        $this->assertTrue(Numbers::isValid("10", 1, 10));
    }

    public function testToInt() {
        // integers
        $this->assertEquals(123, Numbers::toInt(123));

        // floats -> int with different decimals
        $this->assertEquals(12, Numbers::toInt(12.345));
        $this->assertEquals(123, Numbers::toInt(12.345, 1));
        $this->assertEquals(1235, Numbers::toInt(12.345, 2));
        $this->assertEquals(12345, Numbers::toInt(12.345, 3));

        // numeric strings
        $this->assertEquals(123, Numbers::toInt("123"));
        $this->assertEquals(12345, Numbers::toInt("123.45", 2));

        // Date instance
        $d = Date::create(1600000000);
        $this->assertEquals(1600000000, Numbers::toInt($d));

        // invalid or non-numeric
        $this->assertEquals(0, Numbers::toInt(null));
        $this->assertEquals(0, Numbers::toInt([]));
        $this->assertEquals(0, Numbers::toInt(new stdClass()));

        // strings containing numbers but not purely numeric
        $this->assertEquals(123, Numbers::toInt(" 123 "));
        $this->assertEquals(0, Numbers::toInt("123abc"));
    }

    public function testToFloat() {
        // from integer with decimals
        $this->assertEquals(123.45, Numbers::toFloat(12345, 2));
        $this->assertEquals(12345, Numbers::toFloat(12345, 0));

        // from integers and floats
        $this->assertEquals(123.0, Numbers::toFloat(123));
        $this->assertEquals(12.345, Numbers::toFloat(12.345));

        // numeric strings
        $this->assertEquals(123.0, Numbers::toFloat("123"));
        $this->assertEquals(123.45, Numbers::toFloat("123.45"));
        $this->assertEquals(12345.0, Numbers::toFloat("12345", 2));
        $this->assertEquals(123.45, Numbers::toFloat(" 123.45 "));

        // invalid or non-numeric
        $this->assertEquals(0, Numbers::toFloat(null));
        $this->assertEquals(0, Numbers::toFloat([]));
        $this->assertEquals(0, Numbers::toFloat(new stdClass()));
    }

    public function testToIntOrFloat() {
        // integer input returns int
        $val = Numbers::toIntOrFloat(123);
        $this->assertIsInt($val);
        $this->assertSame(123, $val);

        // float input returns float
        $val = Numbers::toIntOrFloat(12.345);
        $this->assertIsFloat($val);
        $this->assertEquals(12.345, $val);

        // numeric strings return floats
        $val = Numbers::toIntOrFloat("123");
        $this->assertIsFloat($val);
        $this->assertEquals(123.0, $val);

        $val = Numbers::toIntOrFloat("123.45");
        $this->assertIsFloat($val);
        $this->assertEquals(123.45, $val);

        // invalid values return 0 (int)
        $this->assertIsInt(Numbers::toIntOrFloat(null));
        $this->assertSame(0, Numbers::toIntOrFloat(null));
        $this->assertSame(0, Numbers::toIntOrFloat([]));
        $this->assertSame(0, Numbers::toIntOrFloat(new stdClass()));
    }

    public function testHasDecimals() {
        $this->assertTrue(Numbers::hasDecimals(1.5));
        $this->assertFalse(Numbers::hasDecimals(2.0));

        // integer inputs should not have decimals
        $this->assertFalse(Numbers::hasDecimals(1));
        $this->assertFalse(Numbers::hasDecimals(0));
    }

    public function testLength() {
        $this->assertEquals(1, Numbers::length(0));
        $this->assertEquals(3, Numbers::length(123));
        $this->assertEquals(3, Numbers::length(-12));
        $this->assertEquals(4, Numbers::length(1000));
    }

    public function testCompare() {
        // integers ascending
        $this->assertEquals(2, Numbers::compare(5, 3));
        $this->assertEquals(-2, Numbers::compare(3, 5));

        // floats ascending
        $this->assertEquals(2.3, Numbers::compare(5.5, 3.2));
        $this->assertEquals(-2.3, Numbers::compare(3.2, 5.5));

        // mixed integer/float
        $this->assertEquals(1.5, Numbers::compare(5, 3.5));
        $this->assertEquals(-1.5, Numbers::compare(3.5, 5));

        // descending (orderAsc = false) flips sign
        $this->assertEquals(-2, Numbers::compare(5, 3, false));
        $this->assertEquals(2, Numbers::compare(3, 5, false));
        $this->assertEquals(-2.3, Numbers::compare(5.5, 3.2, false));
        $this->assertEquals(2.3, Numbers::compare(3.2, 5.5, false));
    }

    public function testRound() {
        // integer input returns same integer
        $this->assertSame(5, Numbers::round(5, 2));

        // rounding down
        $this->assertEquals(1.23, Numbers::round(1.234, 2));

        // rounding up
        $this->assertEquals(1.24, Numbers::round(1.235, 2));

        // different amount of decimals
        $this->assertEquals(1.235, Numbers::round(1.2345, 3));

        // zero decimals -> nearest integer (down/up)
        $this->assertEquals(1, Numbers::round(1.4, 0));
        $this->assertEquals(2, Numbers::round(1.6, 0));
    }

    public function testRoundInt() {
        // integer input returns the same integer
        $this->assertIsInt(Numbers::roundInt(5));
        $this->assertSame(5, Numbers::roundInt(5));

        // normal rounding behavior
        $this->assertSame(1, Numbers::roundInt(1.4));
        $this->assertSame(2, Numbers::roundInt(1.6));

        // negative numbers rounding
        $this->assertSame(-1, Numbers::roundInt(-1.4));
        $this->assertSame(-2, Numbers::roundInt(-1.6));

        // floor behavior when $useFloor = true
        $this->assertSame(1, Numbers::roundInt(1.9, true));
        $this->assertSame(-2, Numbers::roundInt(-1.1, true));
    }

    public function testRandom() {
        // default length (8)
        $r = Numbers::random();
        $this->assertIsInt($r);
        $this->assertEquals(8, Numbers::length($r));

        // various lengths: check several samples to avoid flakiness
        foreach ([ 1, 2, 5, 8 ] as $len) {
            for ($i = 0; $i < 5; $i++) {
                $val = Numbers::random($len);
                $this->assertIsInt($val);
                $this->assertEquals($len, Numbers::length($val));
            }
        }
    }

    public function testFormatInt() {
        // simple decimals from integer
        $this->assertEquals("123,45", Numbers::formatInt(12345, 2));

        // zero value returns default when provided
        $this->assertEquals("-", Numbers::formatInt(0, 2, 1000, "-"));

        // thousands separator and decimals
        $this->assertEquals("12.346", Numbers::formatInt(1234567, 2));

        // no decimals
        $this->assertEquals("123", Numbers::formatInt(123, 0));

        // more decimals
        $this->assertEquals("12,345", Numbers::formatInt(12345, 3));

        // floats as input
        $this->assertEquals("123,45", Numbers::formatInt(123.45, 2));
        $this->assertEquals("1.235", Numbers::formatInt(1234.5, 2));
        $this->assertEquals("124", Numbers::formatInt(123.99, 0));
        $this->assertEquals("12.346", Numbers::formatInt(12345.6, 0));

        // maxForDecimals behavior: large numbers drop decimals when >= maxForDecimals
        $this->assertEquals("12.345", Numbers::formatInt(1234500, 2));

        // forcing maxForDecimals=0 keeps decimals even for large numbers
        $this->assertEquals("12.345,00", Numbers::formatInt(1234500, 2, 0));

        // explicit maxForDecimals variations
        $this->assertEquals("12.345", Numbers::formatInt(1234500, 2, 1000));
        $this->assertEquals("12.345,00", Numbers::formatInt(1234500, 2, 100000));

        // when float equals maxForDecimals decimals are dropped
        $this->assertEquals("12.345", Numbers::formatInt(1234500, 2, 12345));
        $this->assertEquals("12.345,00", Numbers::formatInt(1234500, 2, 12346));

        // smaller large number (123450 -> 1234.5) variations
        $this->assertEquals("1.235", Numbers::formatInt(123450, 2, 1000));
        $this->assertEquals("1.234,50", Numbers::formatInt(123450, 2, 10000));
        $this->assertEquals("1.235", Numbers::formatInt(123450, 2, 1234));
        $this->assertEquals("1.234,50", Numbers::formatInt(123450, 2, 1235));
    }

    public function testFormatFloat() {
        // normal float preserves decimals when below maxForDecimals
        $this->assertEquals("12,35", Numbers::formatFloat(12.345, 2));

        // float exceeds maxForDecimals -> decimals become 0
        $this->assertEquals("1.235", Numbers::formatFloat(1234.56, 2, 1000));

        // maxForDecimals = 0 forces decimals to be allowed
        $this->assertEquals("1.234,56", Numbers::formatFloat(1234.56, 2, 0));

        // integer input => no decimals
        $this->assertEquals("123", Numbers::formatFloat(123, 2));

        // different decimals
        $this->assertEquals("12,346", Numbers::formatFloat(12.3456, 3));

        // maxForDecimals variations: below max keeps decimals
        $this->assertEquals("999,99", Numbers::formatFloat(999.99, 2, 1000));
        // equal to max -> decimals dropped
        $this->assertEquals("1.000", Numbers::formatFloat(1000.0, 2, 1000));
        // well above max -> decimals dropped
        $this->assertEquals("1.235", Numbers::formatFloat(1234.56, 2, 1000));
        // very large max keeps decimals
        $this->assertEquals("1.234,56", Numbers::formatFloat(1234.56, 2, 10000));

        // default when number is zero
        $this->assertEquals("-", Numbers::formatFloat(0, 2, 1000, "-"));

        // custom separators (decimal separator '.' and thousands ',')
        $this->assertEquals("1,234.56", Numbers::formatFloat(1234.56, 2, 0, "", ".", ","));

        // large integer with thousands separator
        $this->assertEquals("1.234.567", Numbers::formatFloat(1234567, 0));
    }

    public function testClampInt() {
        // below min -> returns min
        $this->assertEquals(1, Numbers::clampInt(0, 1, 5));

        // above max -> returns max
        $this->assertEquals(5, Numbers::clampInt(10, 1, 5));

        // within range -> returns the same value
        $this->assertEquals(3, Numbers::clampInt(3, 1, 5));

        // equal to bounds
        $this->assertEquals(1, Numbers::clampInt(1, 1, 5));
        $this->assertEquals(5, Numbers::clampInt(5, 1, 5));

        // negative ranges
        $this->assertEquals(-2, Numbers::clampInt(-3, -2, 2));
        $this->assertEquals(2, Numbers::clampInt(3, -2, 2));
    }

    public function testClampFloat() {
        // below min -> returns min
        $this->assertEquals(1.0, Numbers::clampFloat(0.5, 1.0, 3.0));

        // above max -> returns max
        $this->assertEquals(3.0, Numbers::clampFloat(4.2, 1.0, 3.0));

        // within range -> returns the same value
        $this->assertEquals(2.5, Numbers::clampFloat(2.5, 1.0, 3.0));

        // equal to bounds
        $this->assertEquals(1.0, Numbers::clampFloat(1.0, 1.0, 3.0));
        $this->assertEquals(3.0, Numbers::clampFloat(3.0, 1.0, 3.0));

        // negative ranges
        $this->assertEquals(-2.0, Numbers::clampFloat(-3.0, -2.0, 2.0));
        $this->assertEquals(2.0, Numbers::clampFloat(3.5, -2.0, 2.0));

        // decimals within range
        $this->assertEquals(1.25, Numbers::clampFloat(1.25, 1.0, 2.0));
    }

    public function testMap() {
        // simple mapping
        $this->assertEquals(50, Numbers::map(5, 0, 10, 0, 100));

        // different output range
        $this->assertEquals(150, Numbers::map(5, 0, 10, 100, 200));

        // fractional inputs and outputs
        $this->assertEquals(5, Numbers::map(2.5, 0, 5, 0, 10));

        // negative ranges
        $this->assertEquals(50, Numbers::map(-5, -10, 0, 0, 100));

        // reversed output range
        $this->assertEquals(0.5, Numbers::map(15, 10, 20, 1, 0));

        // value at bounds
        $this->assertEquals(0, Numbers::map(10, 10, 20, 0, 1));
        $this->assertEquals(1, Numbers::map(20, 10, 20, 0, 1));

        // from range zero -> returns toLow (implementation guard)
        $this->assertEquals(10, Numbers::map(0, 0, 0, 10, 20));
    }

    public function testPercent() {
        // basic percent
        $this->assertEquals(40, Numbers::percent(2, 5));

        // zero total -> returns 0
        $this->assertEquals(0, Numbers::percent(5, 0));

        // simple fractions
        $this->assertEquals(25, Numbers::percent(1, 4));

        // decimals and rounding
        $this->assertEquals(33.33, Numbers::percent(1, 3, 2));
        $this->assertEquals(66.67, Numbers::percent(2, 3, 2));
        $this->assertEquals(33.333, Numbers::percent(1, 3, 3));

        // negative numbers
        $this->assertEquals(-25, Numbers::percent(-1, 4));
        $this->assertEquals(-33.33, Numbers::percent(-1, 3, 2));

        // zero numerator -> zero percent
        $this->assertEquals(0, Numbers::percent(0, 100));
        $this->assertEquals(0, Numbers::percent(0.0, 100.0));
        $this->assertEquals(0, Numbers::percent(0, 0));

        // mixed integer/float inputs should behave the same
        $this->assertEquals(40, Numbers::percent(2.0, 5));
        $this->assertEquals(40, Numbers::percent(2, 5.0));
        $this->assertEquals(40, Numbers::percent(2.0, 5.0));
    }

    public function testDivide() {
        // integer division with decimals
        $this->assertEquals(2.5, Numbers::divide(5, 2, 2));
        $this->assertEquals(2, Numbers::divide(4, 2));

        // floats input
        $this->assertEquals(2.5, Numbers::divide(5.0, 2.0, 1));
        $this->assertEquals(2.33, Numbers::divide(7.0, 3.0, 2));

        // rounding behavior
        $this->assertEquals(2.33, Numbers::divide(7, 3, 2));
        $this->assertEquals(2.333, Numbers::divide(7, 3, 3));

        // zero numerator
        $this->assertEquals(0, Numbers::divide(0, 5));

        // zero divisor -> returns 0 (guard)
        $this->assertEquals(0, Numbers::divide(5, 0));

        // negative numbers
        $this->assertEquals(-2.5, Numbers::divide(-5, 2, 1));
        $this->assertEquals(-2.33, Numbers::divide(-7, 3, 2));
    }

    public function testDivideInt() {
        // rounding behavior: 5 / 2 = 2.5 -> rounded to 3
        $this->assertEquals(3, Numbers::divideInt(5, 2));
        // floor behavior: 5 / 2 = 2.5 -> floored to 2
        $this->assertEquals(2, Numbers::divideInt(5, 2, true));

        // exact divisions remain exact
        $this->assertEquals(5, Numbers::divideInt(10, 2));
        $this->assertEquals(5, Numbers::divideInt(10, 2, true));

        // non-integer results with different rounding
        $this->assertEquals(3, Numbers::divideInt(10, 3)); // 3.333 -> 3
        $this->assertEquals(3, Numbers::divideInt(10, 3, true));
        $this->assertEquals(2, Numbers::divideInt(9, 4)); // 2.25 -> 2
        $this->assertEquals(2, Numbers::divideInt(9, 4, true));
        $this->assertEquals(3, Numbers::divideInt(11, 4)); // 2.75 -> 3
        $this->assertEquals(1, Numbers::divideInt(7, 4, true));
        $this->assertEquals(2, Numbers::divideInt(7, 4));

        // zero numerator
        $this->assertEquals(0, Numbers::divideInt(0, 5));

        // zero divisor -> guarded to 0
        $this->assertEquals(0, Numbers::divideInt(5, 0));
        $this->assertEquals(0, Numbers::divideInt(0, 0));

        // negative numbers and divisors
        $this->assertEquals(-3, Numbers::divideInt(-5, 2));
        $this->assertEquals(-3, Numbers::divideInt(-5, 2, true));
        $this->assertEquals(-3, Numbers::divideInt(5, -2));
        $this->assertEquals(-3, Numbers::divideInt(5, -2, true));
    }

    public function testApplyDiscount() {
        // simple percent discount
        $this->assertEquals(90, Numbers::applyDiscount(100, 10));

        // zero percent -> same value
        $this->assertEquals(100, Numbers::applyDiscount(100, 0));
        $this->assertEquals(100, Numbers::applyDiscount(100, 0.0));

        // full discount or higher than 100 -> zero
        $this->assertEquals(0, Numbers::applyDiscount(100, 100));
        $this->assertEquals(0, Numbers::applyDiscount(100, 150));

        // decimal percent (101 * (1 - 0.055) = 95.445)
        $this->assertEquals(95.445, Numbers::applyDiscount(101, 5.5));

        // negative discount is clamped to zero -> no change
        $this->assertEquals(100, Numbers::applyDiscount(100, -10));

        // zero price remains zero regardless of discount
        $this->assertEquals(0, Numbers::applyDiscount(0, 50));
        $this->assertEquals(0.0, Numbers::applyDiscount(0.0, 50));
    }

    public function testApplyIncrement() {
        // zero percent -> same value
        $this->assertEquals(100, Numbers::applyIncrement(100, 0));

        // negative percent treated as 0 -> same value
        $this->assertEquals(100, Numbers::applyIncrement(100, -10));

        // existing rounding check (9% -> ~109.89 -> rounded 110)
        $this->assertEquals(110, round(Numbers::applyIncrement(100, 9)));

        // 10% -> 100 + 100*(10/90) = 111.111...
        $this->assertEqualsWithDelta(111.1111111111, Numbers::applyIncrement(100, 10), 0.0001);

        // decimal percent on larger number
        $this->assertEqualsWithDelta(205.1282051282, Numbers::applyIncrement(200, 2.5), 0.0001);

        // zero price remains zero
        $this->assertEquals(0, Numbers::applyIncrement(0, 50));

        // mixed integer/float inputs
        $this->assertEqualsWithDelta(20, Numbers::applyIncrement(10, 50), 0.0001);
    }

    public function testGetCommonDivisor() {
        // integers
        $this->assertEquals(6, Numbers::getCommonDivisor(48, 18));
        $this->assertEquals(1, Numbers::getCommonDivisor(17, 13));
        $this->assertEquals(12, Numbers::getCommonDivisor(60, 48));

        // zero cases
        $this->assertEquals(5, Numbers::getCommonDivisor(0, 5));
        $this->assertEquals(5, Numbers::getCommonDivisor(5, 0));
        $this->assertEquals(0, Numbers::getCommonDivisor(0, 0));
    }

    public function testIsValidFloat() {
        $this->assertTrue(Numbers::isValidFloat(1.23, 1, null, 2));
        $this->assertFalse(Numbers::isValidFloat(1.234, 1, null, 2));

        // integers with decimals=0
        $this->assertTrue(Numbers::isValidFloat(5, 1, 10, 0));
        $this->assertFalse(Numbers::isValidFloat(0, 1, 10, 0));

        // floats within min/max and allowed decimals
        $this->assertTrue(Numbers::isValidFloat(1.2, 1, 2, 1));
        $this->assertFalse(Numbers::isValidFloat(0.5, 1, null, 1));

        // decimals limit enforcement
        $this->assertFalse(Numbers::isValidFloat(1.2345, 1, null, 3));

        // min/max bounds with floats and ints
        $this->assertTrue(Numbers::isValidFloat(100.00, 1, 200, 2));
        $this->assertFalse(Numbers::isValidFloat(201.0, 1, 200, 1));

        // when min == max boundaries
        $this->assertTrue(Numbers::isValidFloat(5, 5, 5, 0));
        $this->assertFalse(Numbers::isValidFloat(5.1, 5, 5, 1));
    }

    public function testIsValidPrice() {
        // must allow two decimals and respect default min=1
        $this->assertTrue(Numbers::isValidPrice(1.23));
        $this->assertFalse(Numbers::isValidPrice(0.99));

        // rejects more than two decimals
        $this->assertFalse(Numbers::isValidPrice(1.234));

        // min/max bounds
        $this->assertTrue(Numbers::isValidPrice(100.00, 1, 200));
        $this->assertFalse(Numbers::isValidPrice(201.00, 1, 200));

        // integer price allowed
        $this->assertTrue(Numbers::isValidPrice(5, 1, 5));
        $this->assertFalse(Numbers::isValidPrice(5.01, 1, 5));

        // allow zero when min is set to 0
        $this->assertTrue(Numbers::isValidPrice(0, 0, 100));
        $this->assertFalse(Numbers::isValidPrice(0.01, 1, 100));
    }

    public function testRoundCents() {
        // basic rounding down
        $this->assertEquals(1.23, Numbers::roundCents(1.234));

        // rounding up at 5
        $this->assertEquals(1.24, Numbers::roundCents(1.235));

        // small values round to zero
        $this->assertEquals(0, Numbers::roundCents(0.004));

        // negative values
        $this->assertEquals(-1.23, Numbers::roundCents(-1.234));
        $this->assertEquals(-1.24, Numbers::roundCents(-1.235));

        // integer input remains same (as float)
        $this->assertEquals(123.0, Numbers::roundCents(123));

        // values that cross integer boundary
        $this->assertEquals(2.0, Numbers::roundCents(1.999));

        // preserve one-decimal inputs
        $this->assertEquals(1.2, Numbers::roundCents(1.2));
    }

    public function testToCents() {
        // basic float
        $this->assertEquals(1234, Numbers::toCents(12.34));

        // rounding of extra decimals
        $this->assertEquals(1235, Numbers::toCents(12.345));

        // integer input -> cents
        $this->assertEquals(1200, Numbers::toCents(12));

        // negative values
        $this->assertEquals(-123, Numbers::toCents(-1.23));
        $this->assertEquals(-124, Numbers::toCents(-1.235));

        // numeric strings
        $this->assertEquals(1234, Numbers::toCents("12.34"));
        $this->assertEquals(1234, Numbers::toCents(" 12.34 "));
        $this->assertEquals(1200, Numbers::toCents("12"));

        // invalid or non-numeric values -> 0
        $this->assertEquals(0, Numbers::toCents(null));
        $this->assertEquals(0, Numbers::toCents([]));
        $this->assertEquals(0, Numbers::toCents(new stdClass()));
        $this->assertEquals(0, Numbers::toCents("abc"));
    }

    public function testFromCents() {
        // integer cents -> dollars
        $this->assertEquals(12.34, Numbers::fromCents(1234));

        // integer float input
        $this->assertEquals(1234.0, Numbers::fromCents(1234.0));

        // numeric string with decimal
        $this->assertEquals(12.34, Numbers::fromCents("12.34"));

        // numeric string without decimal: implementation returns floatval("1234")
        $this->assertEquals(1234.0, Numbers::fromCents("1234"));

        // trimmed string
        $this->assertEquals(12.34, Numbers::fromCents(" 12.34 "));

        // negative values
        $this->assertEquals(-12.34, Numbers::fromCents(-1234));
        $this->assertEquals(-1234.0, Numbers::fromCents("-1234"));

        // invalid or non-numeric values -> 0.0
        $this->assertEquals(0.0, Numbers::fromCents(null));
        $this->assertEquals(0.0, Numbers::fromCents([]));
        $this->assertEquals(0.0, Numbers::fromCents(new stdClass()));
        $this->assertEquals(0.0, Numbers::fromCents("abc"));
    }

    public function testFormatPrice() {
        // normal price with two decimals
        $this->assertEquals("123,50", Numbers::formatPrice(123.5));

        // zero price returns default '0'
        $this->assertEquals("0", Numbers::formatPrice(0));
        // custom default for zero
        $this->assertEquals("-", Numbers::formatPrice(0, 2, 1000, "-"));

        // large numbers drop decimals when >= maxForDecimals (default 1000)
        $this->assertEquals("1.234.568", Numbers::formatPrice(1234567.891));

        // negative values
        $this->assertEquals("-12,35", Numbers::formatPrice(-12.345));

        // different decimals
        $this->assertEquals("12,346", Numbers::formatPrice(12.3456, 3));

        // integer input -> no decimals
        $this->assertEquals("123", Numbers::formatPrice(123));

        // maxForDecimals boundaries
        $this->assertEquals("999,99", Numbers::formatPrice(999.99, 2, 1000));
        $this->assertEquals("1.000", Numbers::formatPrice(1000.0, 2, 1000));
    }

    public function testFormatCents() {
        // basic cents -> price
        $this->assertEquals("12,34", Numbers::formatCents(1234));

        // small cents -> 1.00
        $this->assertEquals("1,00", Numbers::formatCents(100));

        // zero cents -> empty string (formatFloat default)
        $this->assertEquals("", Numbers::formatCents(0));

        // negative cents
        $this->assertEquals("-12,34", Numbers::formatCents(-1234));

        // thousands separator
        $this->assertEquals("1.235", Numbers::formatCents(123456));

        // different decimals
        $this->assertEquals("12,3", Numbers::formatCents(1234, 1));
        $this->assertEquals("1.235", Numbers::formatCents(123456, 3));

        // numeric string input accepted by PHP casting (loose types)
        $this->assertEquals("12,34", Numbers::formatCents("1234"));
    }

    public function testToPriceString() {
        // small values -> exact dollar amount
        $this->assertEquals("$123", Numbers::toPriceString(123));
        $this->assertEquals("$123", Numbers::toPriceString(123.4));

        // threshold behaviour: 10000 -> kilos rounded to 10 -> not greater than 10
        $this->assertEquals("$10000", Numbers::toPriceString(10000));

        // kilos > 10 -> return in 'k'
        $this->assertEquals("$11k", Numbers::toPriceString(10500));
        $this->assertEquals("$12k", Numbers::toPriceString(12000));

        // millions > 10 -> return in 'm'
        $this->assertEquals("$12m", Numbers::toPriceString(12345678));
    }

    public function testToBytesString() {
        // bytes less than 1024 -> MB
        $this->assertEquals("512 MB", Numbers::toBytesString(512));

        // exactly 1024 -> 1 GB
        $this->assertEquals("1 GB", Numbers::toBytesString(1024));

        // multiple gigabytes
        $this->assertEquals("2 GB", Numbers::toBytesString(2048));

        // terabyte threshold (1024 * 1024 bytes -> 1 TB)
        $this->assertEquals("1 TB", Numbers::toBytesString(1024 * 1024));

        // inGigas = true treats input as gigas -> 1 -> 1 GB
        $this->assertEquals("1 GB", Numbers::toBytesString(1, true));

        // inGigas true large value -> should produce TB
        $this->assertEquals("2 TB", Numbers::toBytesString(2048, true));

        // inGigas true non-edge value -> returns GB
        $this->assertEquals("500 GB", Numbers::toBytesString(500, true));
    }

    public function testZerosPad() {
        // pad integers
        $this->assertEquals("005", Numbers::zerosPad(5, 3));

        // pad floats (string representation preserved)
        $this->assertEquals("012.34", Numbers::zerosPad(12.34, 6));

        // negative number: string "-5" padded on the left
        $this->assertEquals("0-5", Numbers::zerosPad(-5, 3));

        // amount smaller than value length returns original string
        $this->assertEquals("1234", Numbers::zerosPad(1234, 3));

        // pad zero
        $this->assertEquals("0000", Numbers::zerosPad(0, 4));
    }

    public function testCoordinatesDistance() {
        // same point -> zero distance
        $this->assertEquals(0, Numbers::coordinatesDistance(0, 0, 0, 0));

        // one degree longitude at equator ~111.32 km
        $this->assertEqualsWithDelta(111.32, Numbers::coordinatesDistance(0, 0, 0, 1), 1);

        // Paris -> London ~343 km (approx)
        $parisLat = 48.8566;
        $parisLon = 2.3522;
        $londonLat = 51.5074;
        $londonLon = -0.1278;
        $this->assertEqualsWithDelta(343, Numbers::coordinatesDistance($parisLat, $parisLon, $londonLat, $londonLon), 10);

        // New York -> London ~5570 km (approx)
        $nyLat = 40.7128;
        $nyLon = -74.0060;
        $this->assertEqualsWithDelta(5570, Numbers::coordinatesDistance($nyLat, $nyLon, $londonLat, $londonLon), 50);

        // symmetry: distance(a,b) == distance(b,a)
        $d1 = Numbers::coordinatesDistance($nyLat, $nyLon, $londonLat, $londonLon);
        $d2 = Numbers::coordinatesDistance($londonLat, $londonLon, $nyLat, $nyLon);
        $this->assertEqualsWithDelta($d1, $d2, 0.01);
    }

    public function testCalcExpression() {
        // basic arithmetic
        $this->assertEquals(14, Numbers::calcExpression("2+3*4"));

        // text mixed with numbers should be stripped
        $this->assertEquals(14, Numbers::calcExpression("a2+3b*4c"));
        $this->assertEquals(14, Numbers::calcExpression(" 2 + abc3*4 "));

        // functions: floor, ceil, round (both with and without parentheses)
        $this->assertEquals(2, Numbers::calcExpression("floor(2.7)"));
        $this->assertEquals(2, Numbers::calcExpression("floor2.7"));
        $this->assertEquals(2, Numbers::calcExpression("floor 2.7"));
        $this->assertEquals(3, Numbers::calcExpression("ceil(2.1)"));
        $this->assertEquals(2, Numbers::calcExpression("round(1.6)"));

        // function names mixed with text should still work when at start
        $this->assertEquals(2, Numbers::calcExpression("floorabc2.9"));

        // percents: 5% -> 0.05 so 100*5% == 5
        $this->assertEquals(5, Numbers::calcExpression("100*5%"));
        $this->assertEquals(20, Numbers::calcExpression("200*10%"));
        // negative percent
        $this->assertEquals(-5, Numbers::calcExpression("100*-5%"));
        $this->assertEquals(-20, Numbers::calcExpression("200*-10%"));

        // commas as decimal separators
        $this->assertEquals(4, Numbers::calcExpression("1,5+2,5"));

        // +- becomes - | -- becomes +
        $this->assertEquals(5, Numbers::calcExpression("10+-5"));
        $this->assertEquals(15, Numbers::calcExpression("10--5"));
        $this->assertEquals(100_000, Numbers::calcExpression("10**5"));

        // backslash is removed by sanitization -> "2\+3" => "2+3"
        $this->assertEquals(5, Numbers::calcExpression("2\\+3"));

        // invalid or empty expressions return 0
        $this->assertEquals(0, Numbers::calcExpression(""));
        $this->assertEquals(0, Numbers::calcExpression("abc"));
        $this->assertEquals(0, Numbers::calcExpression("2+*3"));
        $this->assertEquals(0, Numbers::calcExpression("round(abc)"));
    }
}
