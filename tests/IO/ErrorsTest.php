<?php
namespace Tests\IO;

use Framework\IO\Errors;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestErrorEnum implements Enum {
    use IsEnum;

    case None;
    case Section;
    case Error;
}

class ErrorsTest extends TestCase {

    #[DataProvider("providerConstruct")]
    public function testConstruct(mixed $input, mixed $checkKey, int $expectedTotal): void {
        $e = new Errors($input);
        if ($input === null && $checkKey === null) {
            $this->assertFalse($e->has());
        } elseif ($checkKey !== null) {
            $this->assertTrue($e->has($checkKey));
        }
        $this->assertEquals($expectedTotal, $e->getTotal());
    }

    public static function providerConstruct(): array {
        return [
            "single"   => [ [ "a" => "msg" ], "a", 1 ],
            "null"     => [ null, null, 0 ],
            "multiple" => [ [ "x" => "1", "y" => "2" ], null, 2 ],
        ];
    }


    #[DataProvider("providerMagicSetAndGet")]
    public function testMagicSetAndGet(string $key, string $value, string $expected, bool $exists = true): void {
        $e = new Errors();
        $e->{$key} = $value;
        $this->assertEquals($exists, $e->has($key));
        $this->assertEquals($expected, $e->{$key});
    }

    public static function providerMagicSetAndGet(): array {
        return [
            "set_b"       => [ "b", "msg", "msg", true ],
            "set_e"       => [ "e", "valid", "valid", true ],
            "overwrite_b" => [ "b", "new", "new", true ],
            "missing_c"   => [ "c", "", "", false ],
        ];
    }


    #[DataProvider("providerIncCount")]
    public function testIncCount(string $key, int $amount, int $expectedAfterFirst, int $expectedAfterSecond, string $newKey, int $expectedNew): void {
        $e = new Errors();
        $e->incCount($key, $amount);
        $this->assertEquals($expectedAfterFirst, $e->get()[$key]);

        // increment again by default amount (1)
        $e->incCount($key);
        $this->assertEquals($expectedAfterSecond, $e->get()[$key]);

        // new counter defaults to 1
        $e->incCount($newKey);
        $this->assertEquals($expectedNew, $e->get()[$newKey]);
    }

    public static function providerIncCount(): array {
        return [
            "counter_2" => [ "counter", 2, 2, 3, "newCounter", 1 ],
            "total_5"   => [ "total", 5, 5, 6, "anotherCounter", 1 ],
        ];
    }


    #[DataProvider("providerForm")]
    public function testForm(string $initialMsg, string $newMsg, string $otherKey, string $otherValue): void {
        $e = new Errors();
        $e->form($initialMsg);

        $this->assertEquals($initialMsg, $e->get()["form"]);
        $this->assertTrue(in_array("form", $e->keys()));

        // form should overwrite existing value
        $e->form($newMsg);
        $this->assertEquals($newMsg, $e->get()["form"]);

        // form should not affect other keys
        $e->add($otherKey, $otherValue);
        $this->assertTrue($e->has($otherKey));
    }

    public static function providerForm(): array {
        return [
            "basic"     => [ "msg", "new", "other", "o" ],
            "empty"     => [ "", "newForm", "other2", "o2" ],
            "overwrite" => [ "initial", "overwritten", "other3", "o3" ],
        ];
    }


    #[DataProvider("providerGlobal")]
    public function testGlobal(string $initial, string $new): void {
        $e = new Errors();
        $e->global($initial);
        $this->assertEquals($initial, $e->get()["global"]);

        // global should be present and not affect other keys
        $this->assertTrue($e->has("global"));
        $this->assertFalse($e->has("nonexistent"));

        // global should overwrite existing value
        $e->global($new);
        $this->assertEquals($new, $e->get()["global"]);
    }

    public static function providerGlobal(): array {
        return [
            "basic"     => [ "msg", "new" ],
            "empty"     => [ "", "newGlobal" ],
            "overwrite" => [ "initial", "overwritten" ],
        ];
    }


    #[DataProvider("providerAdd")]
    public function testAdd(string $key, mixed $args, mixed $expected, bool $shouldExist): void {
        $e = new Errors();
        $e->add($key, ...$args);

        $this->assertEquals($shouldExist, $e->has($key));
        if ($shouldExist) {
            $this->assertEquals($expected, $e->get()[$key]);
        }
    }

    public static function providerAdd(): array {
        return [
            "simple_key_value"   => [ "k", [ "m" ], "m", true ],
            "overwrite_existing" => [ "k", [ "new" ], "new", true ],
            "multiple_values"    => [ "kv", [ "mv", 10, "extra" ], [ "mv", 10, "extra" ], true ],
            "skip_empty_message" => [ "empty", [ "", 1 ], null, false ],
        ];
    }


    #[DataProvider("providerAddIf")]
    public function testAddIf(bool $condition, string $key, string $message, bool $shouldExist): void {
        $e = new Errors();
        $e->addIf($condition, $key, $message);

        $this->assertEquals($shouldExist, $e->has($key));
        if ($shouldExist) {
            $this->assertEquals($message, $e->get()[$key]);
        }
    }

    public static function providerAddIf(): array {
        return [
            "true_condition"  => [ true, "key1", "message1", true ],
            "false_condition" => [ false, "key2", "message2", false ],
            "overwrite"       => [ true, "key1", "newMessage", true ],
            "empty_message"   => [ true, "key3", "", false ],
        ];
    }


    #[DataProvider("providerAddFor")]
    public function testAddFor(Enum|string $section, Enum|string $error, mixed $message, array $values, bool $expectedHasError, mixed $expectedErrorValue, bool $expectedHasSection, mixed $expectedSectionValue, array $initial = []): void {
        $e = new Errors();
        foreach ($initial as $initialSection => $initialErrors) {
            foreach ($initialErrors as $initialError => $initialMessage) {
                $e->addFor($initialSection, $initialError, $initialMessage);
            }
        }

        $e->addFor($section, $error, $message, ...$values);

        $this->assertEquals($expectedHasError, $e->has($error));
        if ($expectedHasError) {
            $errorIndex = $error instanceof Enum ? $error->toString() : $error;
            $this->assertEquals($expectedErrorValue, $e->get()[$errorIndex]);
        }
        if ($expectedHasSection) {
            $sectionIndex = $section instanceof Enum ? $section->toString() : $section;
            $this->assertEquals($expectedSectionValue, $e->get()[$sectionIndex]);
        }
    }

    public static function providerAddFor(): array {
        return [
            "basic"                  => [ "section", "err", "message", [], true, "message", true, 1 ],
            "multiple_errors_same"   => [ "section", "err2", "m2", [], true, "m2", true, 2, [ "section" => [ "err1" => "m1" ]]],
            "with_values"            => [ "section4", "err4", "m4", [ 7, "more" ], true, [ "m4", 7, "more" ], true, 1 ],
            "overwrite_with_values"  => [ "section4", "err4", "newMsg", [ 2 ], true, [ "newMsg", 2 ], true, 1 ],
            "empty_message"          => [ "section4", "empty", "", [ 1 ], false, null, false, null ],
            "enum_section_and_error" => [ TestErrorEnum::Section, TestErrorEnum::Error, "enum message", [ 3 ], true, [ "enum message", 3 ], true, 1 ],
        ];
    }


    #[DataProvider("providerMerge")]
    public function testMerge(array $baseErrors, array $incomingErrors, string $prefix, string $suffix, array $expectedHasKeys, array $expectedValues): void {
        $a = new Errors();
        foreach ($baseErrors as $key => $value) {
            $a->add($key, $value);
        }

        $b = new Errors();
        foreach ($incomingErrors as $key => $value) {
            $b->add($key, $value);
        }

        $a->merge($b, $prefix, $suffix);

        foreach ($expectedHasKeys as $key) {
            $this->assertTrue($a->has($key));
        }
        foreach ($expectedValues as $key => $value) {
            $this->assertEquals($value, $a->get()[$key]);
        }
    }

    public static function providerMerge(): array {
        return [
            "basic_merge_keeps_existing" => [
                [ "one" => "o" ],
                [ "two" => "t" ],
                "",
                "",
                [ "one", "two" ],
                [ "two" => "t" ],
            ],
            "merge_with_prefix_and_suffix" => [
                [ "one" => "o" ],
                [ "two" => "t" ],
                "p_",
                "_s",
                [ "one", "p_two_s" ],
                [ "p_two_s" => "t" ],
            ],
            "merge_with_empty_prefix_suffix" => [
                [ "one" => "o" ],
                [ "three" => "3" ],
                "",
                "",
                [ "one", "three" ],
                [ "three" => "3" ],
            ],
        ];
    }


    #[DataProvider("providerMergeFor")]
    public function testMergeFor(array $initial, string $sec, array $new, array $fix, mixed $expectedKey, mixed $expectedVal, int $expectedCount): void {
        $e = new Errors();
        foreach ($initial as $k => $v) {
            $e->add($k, $v);
        }

        $src = new Errors();
        foreach ($new as $k => $v) {
            $src->add($k, ...(array)$v);
        }

        $e->mergeFor($sec, $src, ...$fix);

        $this->assertEquals($expectedVal, $e->get()[$expectedKey] ?? null);
        $this->assertEquals($expectedCount, $e->get()[$sec] ?? 0);
    }

    public static function providerMergeFor(): array {
        return [
            "basic_merge"   => [ [], "sec2", [ "x" => "m" ], [], "x", "m", 1 ],
            "prefix_suffix" => [ [], "sec3", [ "arr" => [ "val", 1 ] ], [ "p_", "_s" ], "p_arr_s", [ "val", 1 ], 1 ],
            "empty_src"     => [ [ "sec3" => 5 ], "sec3", [], [], "sec3", 5, 5 ],
            "collision"     => [ [ "x" => "old", "sec2" => 1 ], "sec2", [ "x" => "new" ], [], "x", "new", 1 ],
        ];
}


    #[DataProvider("providerHas")]
    public function testHas(array $initial, mixed $args, bool $expected): void {
        $e = new Errors();
        foreach ($initial as $k => $v) {
            $e->add($k, $v);
        }

        if ($args === null) {
            $this->assertEquals($expected, $e->has());
        } else {
            $this->assertEquals($expected, $e->has($args));
        }
    }

    public static function providerHas(): array {
        return [
            "partial_key_match"  => [ [ "field-name-error" => "err" ], "field-name", true ],
            "full_key"           => [ [ "field-name-error" => "err" ], "field-name-error", true ],
            "missing_key"        => [ [ "field-name-error" => "err" ], "field", false ],
            "has_no_args_true"   => [ [ "field-name-error" => "err" ], null, true ],
            "empty_errors_false" => [ [], null, false ],
            "array_any_match"    => [ [ "field-name-error" => "err" ], [ "nope", "field-name" ], true ],
            "enum_key"           => [ [ TestErrorEnum::Error->toString() => "err" ], TestErrorEnum::Error, true ],
        ];
    }


    #[DataProvider("providerKeys")]
    public function testKeys(string $method, array $args, string $expectedKey): void {
        $e = new Errors();
        if ($method === "merge") {
            $src = new Errors();
            $src->add("c", "cv");
            $e->merge($src, ...$args);
        } else {
            $e->$method(...$args);
        }

        $this->assertContains($expectedKey, $e->keys());
    }

    public static function providerKeys(): array {
        return [
            "add_key"        => [ "add",    [ "a", "v" ], "a" ],
            "form_key"       => [ "form",   [ "msg" ], "form" ],
            "global_key"     => [ "global", [ "msg" ], "global" ],
            "merge_with_fix" => [ "merge",  [ "p_", "_s" ], "p_c_s" ],
        ];
    }


    #[DataProvider("providerGetTotal")]
    public function testGetTotal(array $actions, int $expectedTotal): void {
        $e = new Errors();
        foreach ($actions as $method => $args) {
            if ($method === "merge") {
                $other = new Errors();
                foreach ($args["data"] as $k => $v) { $other->add($k, $v); }
                $e->merge($other);
            } elseif ($method === "addFor") {
                $e->addFor(...$args);
            } else {
                $e->add(...$args);
            }
        }

        $this->assertEquals($expectedTotal, $e->getTotal());
    }

    public static function providerGetTotal(): array {
        return [
            "empty_initial"  => [ [], 0 ],
            "single_add"     => [ [ "add" => [ "a", "v" ]], 1 ],
            "add_for_logic"  => [ [ "addFor" => [ "sec", "err", "m" ]], 2 ],
            "merge_increase" => [
                [
                    "add"   => [ "a", "v" ],
                    "merge" => [ "data" => [ "x" => "xv", "y" => "yv" ]],
                ],
                3,
            ],
        ];
    }



    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(array $inputs, array $expected): void {
        $e = new Errors();

        foreach ($inputs as $method => $calls) {
            foreach ($calls as $args) {
                $e->$method(...$args);
            }
        }

        $json = json_encode($e);

        $this->assertNotFalse($json);
        $this->assertEquals($expected, json_decode($json, true));
        $this->assertEquals($expected, $e->jsonSerialize());
    }

    public static function providerJsonSerialize(): array {
        return [
            "empty_errors" => [ [], [] ],
            "mixed_errors" => [
                [
                    "add"    => [ [ "a", "v" ], [ "arr", "val", 1 ]],
                    "form"   => [ [ "fromMsg" ]],
                    "global" => [ [ "globalMsg" ]]
                ],
                [
                    "a"      => "v",
                    "arr"    => [ "val", 1 ],
                    "form"   => "fromMsg",
                    "global" => "globalMsg"
                ],
            ],
        ];
    }
}
