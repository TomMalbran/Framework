<?php
namespace Tests\Utils;

use Framework\Date\Date;
use Framework\Utils\Numbers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

class NumbersTest extends TestCase {

    #[DataProvider("providerIsValid")]
    public function testIsValid(mixed $value, int|null $min, int|null $max, bool $expected): void {
        $this->assertSame($expected, Numbers::isValid($value, $min, $max));
    }

    public static function providerIsValid(): array {
        return [
            "valid_nan"         => [ NAN, null, null, true ],
            "valid_zero_min"    => [ 0, 0, null, true ],
            "valid_int"         => [ 5, null, null, true ],
            "valid_float"       => [ 5.5, null, null, true ],
            "valid_str_int"     => [ "5", null, null, true ],
            "valid_str_float"   => [ "5.5", null, null, true ],

            "invalid_alpha"     => [ "a", null, null, false ],
            "invalid_null"      => [ null, null, null, false ],
            "invalid_array"     => [ [], null, null, false ],
            "invalid_object"    => [ new stdClass(), null, null, false ],
            "invalid_true"      => [ true, null, null, false ],
            "invalid_false"     => [ false, null, null, false ],
            "invalid_empty"     => [ "", null, null, false ],
            "invalid_space"     => [ " ", null, null, false ],

            "min_check_fail"    => [ 0, 1, null, false ],
            "min_max_ok"        => [ 5, 1, 10, true ],
            "min_max_fail_low"  => [ 0, 1, 10, false ],
            "min_max_fail_high" => [ 11, 1, 10, false ],
            "min_max_str"       => [ "10", 1, 10, true ],
        ];
    }


    #[DataProvider("providerToInt")]
    public function testToInt(mixed $value, int $decimals, int $expected): void {
        $this->assertEquals($expected, Numbers::toInt($value, $decimals));
    }

    public static function providerToInt(): array {
        return [
            "integer"              => [ 123, 0, 123 ],
            "float_no_decimals"    => [ 12.345, 0, 12 ],
            "float_1_decimal"      => [ 12.345, 1, 123 ],
            "float_2_decimals"     => [ 12.345, 2, 1235 ],
            "float_3_decimals"     => [ 12.345, 3, 12345 ],
            "numeric_string"       => [ "123", 0, 123 ],
            "numeric_string_2_dec" => [ "123.45", 2, 12345 ],
            "date_instance"        => [ Date::create(1600000000), 0, 1600000000 ],
            "null"                 => [ null, 0, 0 ],
            "array"                => [ [], 0, 0 ],
            "object"               => [ new stdClass(), 0, 0 ],
            "trimmed_string"       => [ " 123 ", 0, 123 ],
            "non_numeric_string"   => [ "123abc", 0, 0 ],
        ];
    }


    #[DataProvider("providerToFloat")]
    public function testToFloat(mixed $value, int $decimals, float $expected): void {
        $this->assertEquals($expected, Numbers::toFloat($value, $decimals));
    }

    public static function providerToFloat(): array {
        return [
            "int_w_decimals"  => [ 12345, 2, 123.45 ],
            "int_no_decimals" => [ 12345, 0, 12345 ],
            "from_int"        => [ 123, 0, 123.0 ],
            "from_float"      => [ 12.345, 0, 12.345 ],
            "num_str"         => [ "123", 0, 123.0 ],
            "num_str_float"   => [ "123.45", 0, 123.45 ],
            "str_w_decimals"  => [ "12345", 2, 12345.0 ],
            "trimmed_str"     => [ " 123.45 ", 0, 123.45 ],
            "null_input"      => [ null, 0, 0 ],
            "array_input"     => [ [], 0, 0 ],
            "obj_input"       => [ new stdClass(), 0, 0 ],
        ];
    }


    #[DataProvider("providerToIntOrFloat")]
    public function testToIntOrFloat(mixed $value, int|float $expected, string $expectedType): void {
        $result = Numbers::toIntOrFloat($value);
        $this->assertEquals($expected, $result);
        $this->assertSame($expectedType, gettype($result));
    }

    public static function providerToIntOrFloat(): array {
        return [
            "integer_input"        => [ 123, 123, "integer" ],
            "float_input"          => [ 12.345, 12.345, "double" ],
            "numeric_string_int"   => [ "123", 123.0, "double" ],
            "numeric_string_float" => [ "123.45", 123.45, "double" ],
            "null_input"           => [ null, 0, "integer" ],
            "array_input"          => [ [], 0, "integer" ],
            "object_input"         => [ new stdClass(), 0, "integer" ],
        ];
    }


    #[DataProvider("providerHasDecimals")]
    public function testHasDecimals(mixed $value, bool $expected): void {
        $this->assertSame($expected, Numbers::hasDecimals($value));
    }

    public static function providerHasDecimals(): array {
        return [
            "float_with_decimals" => [ 1.5, true ],
            "float_no_decimals"   => [ 2.0, false ],
            "integer"             => [ 1, false ],
            "zero"                => [ 0, false ],
        ];
    }


    #[DataProvider("providerLength")]
    public function testLength(int $value, int $expected): void {
        $this->assertEquals($expected, Numbers::length($value));
    }

    public static function providerLength(): array {
        return [
            "zero"         => [ 0, 1 ],
            "positive_int" => [ 123, 3 ],
            "negative_int" => [ -12, 3 ],
            "four_digits"  => [ 1000, 4 ],
        ];
    }


    #[DataProvider("providerCompare")]
    public function testCompare(mixed $a, mixed $b, bool $orderAsc, int|float $expected): void {
        $this->assertEquals($expected, Numbers::compare($a, $b, $orderAsc));
    }

    public static function providerCompare(): array {
        return [
            "integers_ascending"  => [ 5, 3, true, 2 ],
            "integers_descending" => [ 3, 5, true, -2 ],
            "floats_ascending"    => [ 5.5, 3.2, true, 2.3 ],
            "floats_descending"   => [ 3.2, 5.5, true, -2.3 ],
            "mixed_ascending"     => [ 5, 3.5, true, 1.5 ],
            "mixed_descending"    => [ 3.5, 5, true, -1.5 ],
            "desc_order_5_3"      => [ 5, 3, false, -2 ],
            "desc_order_3_5"      => [ 3, 5, false, 2 ],
            "desc_order_5.5_3.2"  => [ 5.5, 3.2, false, -2.3 ],
            "desc_order_3.2_5.5"  => [ 3.2, 5.5, false, 2.3 ],
        ];
    }


    #[DataProvider("providerRound")]
    public function testRound(mixed $value, int $decimals, float $expected): void {
        $this->assertEquals($expected, Numbers::round($value, $decimals));
    }

    public static function providerRound(): array {
        return [
            "integer_input"      => [ 5, 2, 5.0 ],
            "rounding_down"      => [ 1.234, 2, 1.23 ],
            "rounding_up"        => [ 1.235, 2, 1.24 ],
            "three_decimals"     => [ 1.2345, 3, 1.235 ],
            "zero_decimals_down" => [ 1.4, 0, 1.0 ],
            "zero_decimals_up"   => [ 1.6, 0, 2.0 ],
        ];
    }


    #[DataProvider("providerRoundInt")]
    public function testRoundInt(mixed $value, bool $useFloor, int $expected): void {
        $this->assertSame($expected, Numbers::roundInt($value, $useFloor));
    }

    public static function providerRoundInt(): array {
        return [
            "integer_input"          => [ 5, false, 5 ],
            "rounding_down"          => [ 1.4, false, 1 ],
            "rounding_up"            => [ 1.6, false, 2 ],
            "negative_rounding_down" => [ -1.4, false, -1 ],
            "negative_rounding_up"   => [ -1.6, false, -2 ],
            "floor_positive"         => [ 1.9, true, 1 ],
            "floor_negative"         => [ -1.1, true, -2 ],
        ];
    }


    public function testRandom(): void {
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


    #[DataProvider("providerFormatInt")]
    public function testFormatInt(mixed $value, int $decimals, int $maxForDecimals, string $default, string $expected): void {
        $this->assertEquals($expected, Numbers::formatInt($value, $decimals, $maxForDecimals, $default));
    }

    public static function providerFormatInt(): array {
        return [
            "simple_decimals"        => [ 12345, 2, 1000, "", "123,45" ],
            "zero_returns_default"   => [ 0, 2, 1000, "-", "-" ],
            "thousands_sep_decimals" => [ 1234567, 2, 1000, "", "12.346" ],
            "no_decimals"            => [ 123, 0, 1000, "", "123" ],
            "more_decimals"          => [ 12345, 3, 1000, "", "12,345" ],
            "float_basic"            => [ 123.45, 2, 1000, "", "123,45" ],
            "float_large"            => [ 1234.5, 2, 1000, "", "1.235" ],
            "float_rounding"         => [ 123.99, 0, 1000, "", "124" ],
            "float_large_no_dec"     => [ 12345.6, 0, 1000, "", "12.346" ],
            "large_drops_decimals"   => [ 1234500, 2, 1000, "", "12.345" ],
            "max_zero_keeps_dec"     => [ 1234500, 2, 0, "", "12.345,00" ],
            "max_1000"               => [ 1234500, 2, 1000, "", "12.345" ],
            "max_100k"               => [ 1234500, 2, 100000, "", "12.345,00" ],
            "max_equals_val"         => [ 1234500, 2, 12345, "", "12.345" ],
            "max_greater_val"        => [ 1234500, 2, 12346, "", "12.345,00" ],
            "small_max_1k"           => [ 123450, 2, 1000, "", "1.235" ],
            "small_max_10k"          => [ 123450, 2, 10000, "", "1.234,50" ],
            "small_max_1234"         => [ 123450, 2, 1234, "", "1.235" ],
            "small_max_1235"         => [ 123450, 2, 1235, "", "1.234,50" ],
        ];
    }


    #[DataProvider("providerFormatFloat")]
    public function testFormatFloat(mixed $value, int $decimals, int $maxForDecimals, string $default, string $decimalSeparator, string $thousandsSeparator, string $expected): void {
        $this->assertEquals($expected, Numbers::formatFloat($value, $decimals, $maxForDecimals, $default, $decimalSeparator, $thousandsSeparator));
    }

    public static function providerFormatFloat(): array {
        return [
            "normal_preserves_dec" => [ 12.345, 2, 1000, "", ",", ".", "12,35" ],
            "exceeds_max"          => [ 1234.56, 2, 1000, "", ",", ".", "1.235" ],
            "max_zero_keeps_dec"   => [ 1234.56, 2, 0, "", ",", ".", "1.234,56" ],
            "int_no_dec"           => [ 123, 2, 1000, "", ",", ".", "123" ],
            "diff_decimals"        => [ 12.3456, 3, 1000, "", ",", ".", "12,346" ],
            "below_max_keeps_dec"  => [ 999.99, 2, 1000, "", ",", ".", "999,99" ],
            "at_max_drops_dec"     => [ 1000.0, 2, 1000, "", ",", ".", "1.000" ],
            "above_max_drops_dec"  => [ 1234.56, 2, 1000, "", ",", ".", "1.235" ],
            "large_max_keeps_dec"  => [ 1234.56, 2, 10000, "", ",", ".", "1.234,56" ],
            "zero_default"         => [ 0, 2, 1000, "-", ",", ".", "-" ],
            "custom_seps"          => [ 1234.56, 2, 0, "", ".", ",", "1,234.56" ],
            "large_int_thousands"  => [ 1234567, 0, 1000, "", ",", ".", "1.234.567" ],
        ];
    }


    #[DataProvider("providerClampInt")]
    public function testClampInt(mixed $value, int $min, int $max, int $expected): void {
        $this->assertEquals($expected, Numbers::clampInt($value, $min, $max));
    }

    public static function providerClampInt(): array {
        return [
            "below_min"      => [ 0, 1, 5, 1 ],
            "above_max"      => [ 10, 1, 5, 5 ],
            "within_range"   => [ 3, 1, 5, 3 ],
            "equal_to_min"   => [ 1, 1, 5, 1 ],
            "equal_to_max"   => [ 5, 1, 5, 5 ],
            "negative_below" => [ -3, -2, 2, -2 ],
            "negative_above" => [ 3, -2, 2, 2 ],
        ];
    }


    #[DataProvider("providerClampFloat")]
    public function testClampFloat(mixed $value, float $min, float $max, float $expected): void {
        $this->assertEquals($expected, Numbers::clampFloat($value, $min, $max));
    }

    public static function providerClampFloat(): array {
        return [
            "below_min"         => [ 0.5, 1.0, 3.0, 1.0 ],
            "above_max"         => [ 4.2, 1.0, 3.0, 3.0 ],
            "within_range"      => [ 2.5, 1.0, 3.0, 2.5 ],
            "equal_to_min"      => [ 1.0, 1.0, 3.0, 1.0 ],
            "equal_to_max"      => [ 3.0, 1.0, 3.0, 3.0 ],
            "negative_below"    => [ -3.0, -2.0, 2.0, -2.0 ],
            "negative_above"    => [ 3.5, -2.0, 2.0, 2.0 ],
            "decimals_in_range" => [ 1.25, 1.0, 2.0, 1.25 ],
        ];
    }


    #[DataProvider("providerMap")]
    public function testMap(mixed $value, int|float $fromLow, int|float $fromHigh, int|float $toLow, int|float $toHigh, int|float $expected): void {
        $this->assertEquals($expected, Numbers::map($value, $fromLow, $fromHigh, $toLow, $toHigh));
    }

    public static function providerMap(): array {
        return [
            "simple_mapping"         => [ 5, 0, 10, 0, 100, 50 ],
            "different_output_range" => [ 5, 0, 10, 100, 200, 150 ],
            "fractional_inputs"      => [ 2.5, 0, 5, 0, 10, 5 ],
            "negative_ranges"        => [ -5, -10, 0, 0, 100, 50 ],
            "reversed_output_range"  => [ 15, 10, 20, 1, 0, 0.5 ],
            "value_at_low_bound"     => [ 10, 10, 20, 0, 1, 0 ],
            "value_at_high_bound"    => [ 20, 10, 20, 0, 1, 1 ],
            "from_range_zero"        => [ 0, 0, 0, 10, 20, 10 ],
        ];
    }


    #[DataProvider("providerPercent")]
    public function testPercent(mixed $numerator, mixed $total, int $decimals, int|float $expected): void {
        $this->assertEquals($expected, Numbers::percent($numerator, $total, $decimals));
    }

    public static function providerPercent(): array {
        return [
            "basic_percent"           => [ 2, 5, 0, 40 ],
            "zero_total"              => [ 5, 0, 0, 0 ],
            "simple_fraction_quarter" => [ 1, 4, 0, 25 ],
            "decimals_2_one_third"    => [ 1, 3, 2, 33.33 ],
            "decimals_2_two_thirds"   => [ 2, 3, 2, 66.67 ],
            "decimals_3_one_third"    => [ 1, 3, 3, 33.333 ],
            "negative_numerator"      => [ -1, 4, 0, -25 ],
            "negative_with_decimals"  => [ -1, 3, 2, -33.33 ],
            "zero_numerator"          => [ 0, 100, 0, 0 ],
            "zero_numerator_float"    => [ 0.0, 100.0, 0, 0 ],
            "zero_both"               => [ 0, 0, 0, 0 ],
            "mixed_float_int_1"       => [ 2.0, 5, 0, 40 ],
            "mixed_int_float_1"       => [ 2, 5.0, 0, 40 ],
            "mixed_both_float"        => [ 2.0, 5.0, 0, 40 ],
        ];
    }


    #[DataProvider("providerDivide")]
    public function testDivide(mixed $numerator, mixed $divisor, int $decimals, float $expected): void {
        $result = Numbers::divide($numerator, $divisor, $decimals);
        $this->assertIsFloat($result);
        $this->assertEquals($expected, $result);
    }

    public static function providerDivide(): array {
        return [
            "int_div_decimals" => [ 5, 2, 2, 2.5 ],
            "exact_div"        => [ 4, 2, 0, 2.0 ],
            "float_1_dec"      => [ 5.0, 2.0, 1, 2.5 ],
            "float_2_dec"      => [ 7.0, 3.0, 2, 2.33 ],
            "round_2_dec"      => [ 7, 3, 2, 2.33 ],
            "round_3_dec"      => [ 7, 3, 3, 2.333 ],
            "zero_num"         => [ 0, 5, 0, 0.0 ],
            "zero_div"         => [ 5, 0, 0, 0.0 ],
            "neg_num_1_dec"    => [ -5, 2, 1, -2.5 ],
            "neg_num_2_dec"    => [ -7, 3, 2, -2.33 ],
        ];
    }


    #[DataProvider("providerDivideInt")]
    public function testDivideInt(mixed $numerator, mixed $divisor, bool $useFloor, int $expected): void {
        $result = Numbers::divideInt($numerator, $divisor, $useFloor);
        $this->assertIsInt($result);
        $this->assertEquals($expected, $result);
    }

    public static function providerDivideInt(): array {
        return [
            "round_5_2"        => [ 5, 2, false, 3 ],
            "floor_5_2"        => [ 5, 2, true, 2 ],
            "exact_10_2"       => [ 10, 2, false, 5 ],
            "exact_10_2_floor" => [ 10, 2, true, 5 ],
            "round_10_3"       => [ 10, 3, false, 3 ],
            "floor_10_3"       => [ 10, 3, true, 3 ],
            "round_9_4"        => [ 9, 4, false, 2 ],
            "floor_9_4"        => [ 9, 4, true, 2 ],
            "round_11_4"       => [ 11, 4, false, 3 ],
            "floor_7_4"        => [ 7, 4, true, 1 ],
            "round_7_4"        => [ 7, 4, false, 2 ],
            "zero_num"         => [ 0, 5, false, 0 ],
            "zero_div"         => [ 5, 0, false, 0 ],
            "zero_both"        => [ 0, 0, false, 0 ],
            "neg_num"          => [ -5, 2, false, -3 ],
            "neg_num_floor"    => [ -5, 2, true, -3 ],
            "neg_div"          => [ 5, -2, false, -3 ],
            "neg_div_floor"    => [ 5, -2, true, -3 ],
        ];
    }


    #[DataProvider("providerApplyDiscount")]
    public function testApplyDiscount(mixed $value, int|float $percent, int|float $expected): void {
        $this->assertEquals($expected, Numbers::applyDiscount($value, $percent));
    }

    public static function providerApplyDiscount(): array {
        return [
            "simple_discount"      => [ 100, 10, 90 ],
            "zero_pct"             => [ 100, 0, 100 ],
            "zero_pct_float"       => [ 100, 0.0, 100 ],
            "full_discount"        => [ 100, 100, 0 ],
            "discount_over_100"    => [ 100, 150, 0 ],
            "decimal_pct"          => [ 101, 5.5, 95.445 ],
            "neg_discount_clamped" => [ 100, -10, 100 ],
            "zero_val_discount"    => [ 0, 50, 0 ],
            "zero_val_float_disc"  => [ 0.0, 50, 0.0 ],
        ];
    }


    #[DataProvider("providerApplyIncrement")]
    public function testApplyIncrement(mixed $value, int|float $percent, int|float $expected, float $delta = 0.0001): void {
        if ($delta > 0) {
            $this->assertEqualsWithDelta($expected, Numbers::applyIncrement($value, $percent), $delta);
        } else {
            $this->assertEquals($expected, Numbers::applyIncrement($value, $percent));
        }
    }

    public static function providerApplyIncrement(): array {
        return [
            "zero_percent"     => [ 100, 0, 100, 0 ],
            "negative_percent" => [ 100, -10, 100, 0 ],
            "nine_percent"     => [ 100, 9, 109.8901098901, 0.0001 ],
            "ten_percent"      => [ 100, 10, 111.1111111111, 0.0001 ],
            "decimal_percent"  => [ 200, 2.5, 205.1282051282, 0.0001 ],
            "zero_price"       => [ 0, 50, 0, 0 ],
            "mixed_int_float"  => [ 10, 50, 20, 0.0001 ],
        ];
    }


    #[DataProvider("providerGetCommonDivisor")]
    public function testGetCommonDivisor(int $a, int $b, int $expected): void {
        $this->assertEquals($expected, Numbers::getCommonDivisor($a, $b));
    }

    public static function providerGetCommonDivisor(): array {
        return [
            "integers_48_18" => [ 48, 18, 6 ],
            "integers_17_13" => [ 17, 13, 1 ],
            "integers_60_48" => [ 60, 48, 12 ],
            "zero_a"         => [ 0, 5, 5 ],
            "zero_b"         => [ 5, 0, 5 ],
            "zero_both"      => [ 0, 0, 0 ],
        ];
    }


    #[DataProvider("providerIsValidFloat")]
    public function testIsValidFloat(mixed $value, int $min, ?int $max, int $decimals, bool $expected): void {
        $this->assertSame($expected, Numbers::isValidFloat($value, $min, $max, $decimals));
    }

    public static function providerIsValidFloat(): array {
        return [
            "valid_1_23_dec_2"        => [ 1.23, 1, null, 2, true ],
            "invalid_1_234_dec_2"     => [ 1.234, 1, null, 2, false ],
            "valid_int_dec_0"         => [ 5, 1, 10, 0, true ],
            "invalid_int_below_min"   => [ 0, 1, 10, 0, false ],
            "valid_1_2_dec_1"         => [ 1.2, 1, 2, 1, true ],
            "invalid_0_5_below_min"   => [ 0.5, 1, null, 1, false ],
            "invalid_1_2345_dec_3"    => [ 1.2345, 1, null, 3, false ],
            "valid_100_00_in_range"   => [ 100.00, 1, 200, 2, true ],
            "invalid_201_0_above_max" => [ 201.0, 1, 200, 1, false ],
            "valid_eq_min_max"        => [ 5, 5, 5, 0, true ],
            "invalid_5_1_not_eq"      => [ 5.1, 5, 5, 1, false ],
        ];
    }


    #[DataProvider("providerIsValidPrice")]
    public function testIsValidPrice(mixed $value, int $min, ?int $max, bool $expected): void {
        $this->assertSame($expected, Numbers::isValidPrice($value, $min, $max));
    }

    public static function providerIsValidPrice(): array {
        return [
            "valid_1_23"             => [ 1.23, 1, null, true ],
            "invalid_0_99"           => [ 0.99, 1, null, false ],
            "invalid_1_234"          => [ 1.234, 1, null, false ],
            "valid_100_in_range"     => [ 100.00, 1, 200, true ],
            "invalid_201_above_max"  => [ 201.00, 1, 200, false ],
            "valid_int_at_max"       => [ 5, 1, 5, true ],
            "invalid_5_01_above_max" => [ 5.01, 1, 5, false ],
            "valid_zero_allowed"     => [ 0, 0, 100, true ],
            "invalid_0_01_above_min" => [ 0.01, 1, 100, false ],
        ];
    }


    #[DataProvider("providerRoundCents")]
    public function testRoundCents(mixed $value, float|int $expected): void {
        $this->assertEquals($expected, Numbers::roundCents($value));
    }

    public static function providerRoundCents(): array {
        return [
            "rounding_down"        => [ 1.234, 1.23 ],
            "rounding_up_at_5"     => [ 1.235, 1.24 ],
            "small_values_to_zero" => [ 0.004, 0 ],
            "negative_rounding_down" => [ -1.234, -1.23 ],
            "negative_rounding_up" => [ -1.235, -1.24 ],
            "integer_input"        => [ 123, 123.0 ],
            "cross_integer_boundary" => [ 1.999, 2.0 ],
            "preserve_one_decimal" => [ 1.2, 1.2 ],
        ];
    }


    #[DataProvider("providerToCents")]
    public function testToCents(mixed $value, int $expected): void {
        $this->assertEquals($expected, Numbers::toCents($value));
    }

    public static function providerToCents(): array {
        return [
            "basic_float"         => [ 12.34, 1234 ],
            "rounding_extra_decs" => [ 12.345, 1235 ],
            "integer_input"       => [ 12, 1200 ],
            "negative_float"      => [ -1.23, -123 ],
            "negative_rounding"   => [ -1.235, -124 ],
            "numeric_string"      => [ "12.34", 1234 ],
            "trimmed_string"      => [ " 12.34 ", 1234 ],
            "numeric_string_int"  => [ "12", 1200 ],
            "null_input"          => [ null, 0 ],
            "array_input"         => [ [], 0 ],
            "object_input"        => [ new stdClass(), 0 ],
            "non_numeric_string"  => [ "abc", 0 ],
        ];
    }


    #[DataProvider("providerFromCents")]
    public function testFromCents(mixed $value, float $expected): void {
        $this->assertEquals($expected, Numbers::fromCents($value));
    }

    public static function providerFromCents(): array {
        return [
            "integer_cents"          => [ 1234, 12.34 ],
            "float_input"            => [ 1234.0, 1234.0 ],
            "numeric_string_decimal" => [ "12.34", 12.34 ],
            "numeric_string_no_dec"  => [ "1234", 1234.0 ],
            "trimmed_string"         => [ " 12.34 ", 12.34 ],
            "negative_int"           => [ -1234, -12.34 ],
            "negative_string"        => [ "-1234", -1234.0 ],
            "null_input"             => [ null, 0.0 ],
            "array_input"            => [ [], 0.0 ],
            "object_input"           => [ new stdClass(), 0.0 ],
            "non_numeric_string"     => [ "abc", 0.0 ],
        ];
    }


    #[DataProvider("providerFormatPrice")]
    public function testFormatPrice(mixed $value, int $decimals, int $maxForDecimals, string $default, string $expected): void {
        $this->assertEquals($expected, Numbers::formatPrice($value, $decimals, $maxForDecimals, $default));
    }

    public static function providerFormatPrice(): array {
        return [
            "norm_2dec"           => [ 123.5, 2, 1000, "0", "123,50" ],
            "zero_default"        => [ 0, 2, 1000, "0", "0" ],
            "zero_custom_default" => [ 0, 2, 1000, "-", "-" ],
            "large_drop_dec"      => [ 1234567.891, 2, 1000, "0", "1.234.568" ],
            "neg_values"          => [ -12.345, 2, 1000, "0", "-12,35" ],
            "diff_dec"            => [ 12.3456, 3, 1000, "0", "12,346" ],
            "int_no_dec"          => [ 123, 2, 1000, "0", "123" ],
            "max_below"           => [ 999.99, 2, 1000, "0", "999,99" ],
            "max_at_threshold"    => [ 1000.0, 2, 1000, "0", "1.000" ],
        ];
    }


    #[DataProvider("providerFormatCents")]
    public function testFormatCents(mixed $value, int $decimals, string $expected): void {
        $this->assertEquals($expected, Numbers::formatCents($value, $decimals));
    }

    public static function providerFormatCents(): array {
        return [
            "basic_cents"          => [ 1234, 2, "12,34" ],
            "small_cents"          => [ 100, 2, "1,00" ],
            "zero_cents"           => [ 0, 2, "" ],
            "negative_cents"       => [ -1234, 2, "-12,34" ],
            "thousands_separator"  => [ 123456, 2, "1.235" ],
            "one_decimal"          => [ 1234, 1, "12,3" ],
            "three_decimals"       => [ 123456, 3, "1.235" ],
            "numeric_string_input" => [ "1234", 2, "12,34" ],
        ];
    }


    #[DataProvider("providerToPriceString")]
    public function testToPriceString(mixed $value, string $expected): void {
        $this->assertEquals($expected, Numbers::toPriceString($value));
    }

    public static function providerToPriceString(): array {
        return [
            "small_int"          => [ 123, "$123" ],
            "small_float"        => [ 123.4, "$123" ],
            "threshold_at_10000" => [ 10000, "$10000" ],
            "kilos_above_10"     => [ 10500, "$11k" ],
            "kilos_exact_12000"  => [ 12000, "$12k" ],
            "millions_above_10"  => [ 12345678, "$12m" ],
        ];
    }


    #[DataProvider("providerToBytesString")]
    public function testToBytesString(mixed $value, bool $inGigas, string $expected): void {
        $this->assertEquals($expected, Numbers::toBytesString($value, $inGigas));
    }

    public static function providerToBytesString(): array {
        return [
            "bytes_512_mb"     => [ 512, false, "512 MB" ],
            "bytes_1024_gb"    => [ 1024, false, "1 GB" ],
            "bytes_2048_gb"    => [ 2048, false, "2 GB" ],
            "bytes_1048576_tb" => [ 1024 * 1024, false, "1 TB" ],
            "gigas_1_gb"       => [ 1, true, "1 GB" ],
            "gigas_2048_tb"    => [ 2048, true, "2 TB" ],
            "gigas_500_gb"     => [ 500, true, "500 GB" ],
        ];
    }


    #[DataProvider("providerZerosPad")]
    public function testZerosPad(mixed $value, int $length, string $expected): void {
        $this->assertEquals($expected, Numbers::zerosPad($value, $length));
    }

    public static function providerZerosPad(): array {
        return [
            "pad_integers"               => [ 5, 3, "005" ],
            "pad_floats"                 => [ 12.34, 6, "012.34" ],
            "negative_number"            => [ -5, 3, "0-5" ],
            "amount_smaller_than_length" => [ 1234, 3, "1234" ],
            "pad_zero"                   => [ 0, 4, "0000" ],
        ];
    }


    #[DataProvider("providerCoordinatesDistance")]
    public function testCoordinatesDistance(float $lat1, float $lon1, float $lat2, float $lon2, float $expected, float $delta): void {
        $this->assertEqualsWithDelta($expected, Numbers::coordinatesDistance($lat1, $lon1, $lat2, $lon2), $delta);
    }

    public static function providerCoordinatesDistance(): array {
        return [
            "same_point"           => [ 0, 0, 0, 0, 0, 0 ],
            "one_degree_longitude" => [ 0, 0, 0, 1, 111.32, 1 ],
            "paris_london"         => [ 48.8566, 2.3522, 51.5074, -0.1278, 343, 10 ],
            "new_york_london"      => [ 40.7128, -74.0060, 51.5074, -0.1278, 5570, 50 ],
            "symmetry_ab"          => [ 40.7128, -74.0060, 51.5074, -0.1278, 5570, 50 ],
            "symmetry_ba"          => [ 51.5074, -0.1278, 40.7128, -74.0060, 5570, 50 ],
        ];
    }


    #[DataProvider("providerCalcExpression")]
    public function testCalcExpression(string $expression, int|float $expected): void {
        $this->assertEquals($expected, Numbers::calcExpression($expression));
    }

    public static function providerCalcExpression(): array {
        return [
            "basic_arithmetic"          => [ "2+3*4", 14 ],
            "text_mixed_with_numbers"   => [ "a2+3b*4c", 14 ],
            "text_mixed_with_spaces"    => [ " 2 + abc3*4 ", 14 ],
            "floor_with_parentheses"    => [ "floor(2.7)", 2 ],
            "floor_without_parentheses" => [ "floor2.7", 2 ],
            "floor_with_space"          => [ "floor 2.7", 2 ],
            "ceil_with_parentheses"     => [ "ceil(2.1)", 3 ],
            "round_with_parentheses"    => [ "round(1.6)", 2 ],
            "function_name_with_text"   => [ "floor abc2.9", 2 ],
            "percent_basic"             => [ "100*5%", 5 ],
            "percent_200"               => [ "200*10%", 20 ],
            "percent_negative_1"        => [ "100*-5%", -5 ],
            "percent_negative_2"        => [ "200*-10%", -20 ],
            "comma_decimal_separator"   => [ "1,5+2,5", 4 ],
            "plus_minus_becomes_minus"  => [ "10+-5", 5 ],
            "minus_minus_becomes_plus"  => [ "10--5", 15 ],
            "power_operator"            => [ "10**5", 100_000 ],
            "backslash_removed"         => [ "2\\+3", 5 ],
            "empty_expression"          => [ "", 0 ],
            "invalid_abc"               => [ "abc", 0 ],
            "invalid_operator_sequence" => [ "2+*3", 0 ],
            "invalid_function_argument" => [ "round(abc)", 0 ],
        ];
    }
}
