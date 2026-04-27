<?php
namespace Tests\Enum;

use Framework\Enum\Enum;
use Framework\Enum\Map;
use Framework\Enum\IsEnum;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestMapPlainEnum implements Enum {
    use IsEnum;

    case None;
    case Apple;
    case Banana;
}

enum TestMapBackedEnum: string implements Enum {
    use IsEnum;

    case None  = "";
    case Red   = "red";
    case Green = "green";
}

class MapTest extends TestCase {

    #[DataProvider("providerIsEmpty")]
    public function testIsEmpty(array $operations, bool $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->isEmpty());
    }

    public static function providerIsEmpty(): array {
        return [
            "empty"  => [ [], true ],
            "filled" => [ [[ TestMapPlainEnum::Apple, 1 ]], false ],
        ];
    }


    #[DataProvider("providerIsNotEmpty")]
    public function testIsNotEmpty(array $operations, bool $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->isNotEmpty());
    }

    public static function providerIsNotEmpty(): array {
        return [
            "empty"  => [ [], false ],
            "filled" => [ [[ TestMapPlainEnum::Apple, 1 ]], true ],
        ];
    }


    #[DataProvider("providerSet")]
    public function testSet(array $operations, array $hasChecks, array $getChecks): void {
        $map = $this->createMap($operations);

        foreach ($hasChecks as [ $key, $expected ]) {
            $this->assertSame($expected, $map->has($key));
        }
        foreach ($getChecks as [ $key, $expected ]) {
            $this->assertSame($expected, $map->get($key));
        }
    }

    public static function providerSet(): array {
        return [
            "set_and_overwrite" => [
                [
                    [ TestMapPlainEnum::Apple, "value" ],
                    [ TestMapPlainEnum::Apple, "new value" ],
                    [ TestMapPlainEnum::Banana, "banana value" ],
                    [ TestMapBackedEnum::Red, "red value" ],
                    [ TestMapPlainEnum::None, null ],
                ],
                [
                    [ TestMapPlainEnum::Apple, true ],
                    [ TestMapPlainEnum::None, true ],
                ],
                [
                    [ TestMapPlainEnum::Apple, "new value" ],
                    [ TestMapPlainEnum::Banana, "banana value" ],
                    [ TestMapBackedEnum::Red, "red value" ],
                    [ TestMapPlainEnum::None, null ],
                ],
            ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(array $operations, Enum $key, bool $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->has($key));
    }

    public static function providerHas(): array {
        return [
            "missing_plain"  => [ [], TestMapPlainEnum::Apple, false ],
            "existing_plain" => [
                [[ TestMapPlainEnum::Apple, 123 ]],
                TestMapPlainEnum::Apple,
                true,
            ],
            "other_plain_missing" => [
                [[ TestMapPlainEnum::Apple, 123 ]],
                TestMapPlainEnum::Banana,
                false,
            ],
            "missing_backed" => [
                [[ TestMapPlainEnum::Apple, 123 ]],
                TestMapBackedEnum::Red,
                false,
            ],
            "existing_backed" => [
                [
                    [ TestMapPlainEnum::Apple, 123 ],
                    [ TestMapBackedEnum::Red, "red" ],
                ],
                TestMapBackedEnum::Red,
                true,
            ],
        ];
    }


    #[DataProvider("providerGet")]
    public function testGet(array $operations, Enum $key, mixed $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->get($key));
    }

    public static function providerGet(): array {
        return [
            "missing_plain"  => [ [], TestMapPlainEnum::Apple, null ],
            "existing_plain" => [
                [[ TestMapPlainEnum::Apple, "x" ]],
                TestMapPlainEnum::Apple,
                "x",
            ],
            "missing_backed" => [
                [[ TestMapPlainEnum::Apple, "x" ]],
                TestMapBackedEnum::Red,
                null,
            ],
            "existing_backed" => [
                [
                    [ TestMapPlainEnum::Apple, "x" ],
                    [ TestMapBackedEnum::Red, "red" ],
                ],
                TestMapBackedEnum::Red,
                "red",
            ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(array $operations, Enum $key, int $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->getInt($key));
    }

    public static function providerGetInt(): array {
        return [
            "missing_plain" => [ [], TestMapPlainEnum::Apple, 0 ],
            "plain_int"     => [
                [[ TestMapPlainEnum::Apple, 42 ]],
                TestMapPlainEnum::Apple,
                42,
            ],
            "plain_string" => [
                [[ TestMapPlainEnum::Apple, "42" ]],
                TestMapPlainEnum::Apple,
                42,
            ],
            "plain_float" => [
                [[ TestMapPlainEnum::Banana, 7.9 ]],
                TestMapPlainEnum::Banana,
                8,
            ],
            "missing_backed" => [ [], TestMapBackedEnum::Red, 0 ],
            "backed_string"  => [
                [[ TestMapBackedEnum::Red, "100" ]],
                TestMapBackedEnum::Red,
                100,
            ],
            "plain_invalid" => [
                [[ TestMapPlainEnum::None, "not a number" ]],
                TestMapPlainEnum::None,
                0,
            ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(array $operations, Enum $key, string $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->getString($key));
    }

    public static function providerGetString(): array {
        return [
            "missing_plain" => [ [], TestMapPlainEnum::Apple, "" ],
            "plain_string"  => [
                [[ TestMapPlainEnum::Apple, "hello" ]],
                TestMapPlainEnum::Apple,
                "hello",
            ],
            "plain_int" => [
                [[ TestMapPlainEnum::Apple, 55 ]],
                TestMapPlainEnum::Apple,
                "55",
            ],
            "missing_backed" => [ [], TestMapBackedEnum::Red, "" ],
            "backed_string"  => [
                [[ TestMapBackedEnum::Red, "red value" ]],
                TestMapBackedEnum::Red,
                "red value",
            ],
            "plain_null" => [
                [[ TestMapPlainEnum::None, null ]],
                TestMapPlainEnum::None,
                "",
            ],
        ];
    }


    #[DataProvider("providerCount")]
    public function testCount(array $operations, int $expected): void {
        $map = $this->createMap($operations);
        $this->assertSame($expected, $map->count());
    }

    public static function providerCount(): array {
        return [
            "empty"  => [ [], 0 ],
            "filled" => [
                [
                    [ TestMapPlainEnum::Apple, 1 ],
                    [ TestMapPlainEnum::Banana, 2 ],
                ],
                2,
            ],
        ];
    }


    #[DataProvider("providerGetIterator")]
    public function testGetIterator(array $operations, array $expected): void {
        $map = $this->createMap($operations);

        $collected = [];
        foreach ($map->getIterator() as $key => $value) {
            $collected[$key->toString()] = $value;
        }

        $this->assertSame($expected, $collected);
    }

    public static function providerGetIterator(): array {
        return [
            "empty"  => [ [], [] ],
            "filled" => [
                [
                    [ TestMapPlainEnum::Apple, "a" ],
                    [ TestMapPlainEnum::Banana, "b" ],
                ],
                [ "Apple" => "a", "Banana" => "b" ],
            ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(array $operations, array $expected, bool $expectEmpty): void {
        $map = $this->createMap($operations);
        $serialized = $map->jsonSerialize();

        if ($expectEmpty) {
            $this->assertSame([], $serialized);
            return;
        }
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $serialized);
            $this->assertSame($value, $serialized[$key]);
        }
    }

    public static function providerJsonSerialize(): array {
        return [
            "empty" => [ [], [], true ],
            "mixed" => [
                [
                    [ TestMapPlainEnum::Apple, "a" ],
                    [ TestMapBackedEnum::Red, "r" ],
                ],
                [ "Apple" => "a", "red" => "r" ],
                false,
            ],
        ];
    }


    private function createMap(array $operations): Map {
        $map = new Map();
        foreach ($operations as [ $key, $value ]) {
            $map->set($key, $value);
        }
        return $map;
    }
}
