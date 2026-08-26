<?php
namespace Tests\IO;

use Framework\IO\Select;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Enum\Map;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestSelectEnum implements Enum {
    use IsEnum;

    case None;
    case One;
    case Two;
}

class SelectTest extends TestCase {

    #[DataProvider("providerConstruct")]
    public function testConstruct(
        Enum|int|string $key,
        Enum|string $value,
        int $expectedId,
        string $expectedField,
        int|string $expectedKey,
        string $expectedValue,
        array $expectedExtras = [],
    ): void {
        $select = new Select($key, $value);

        $this->assertSame($expectedId, $select->id);
        $this->assertSame($expectedField, $select->field);
        $this->assertSame($expectedKey, $select->key);
        $this->assertSame($expectedValue, $select->value);
        $this->assertSame($expectedExtras, $select->getExtras());
    }

    public static function providerConstruct(): array {
        return [
            "string" => [ "abc", "Value", 0, "abc", "abc", "Value" ],
            "int"    => [ 1, "One", 1, "1", 1, "One" ],
            "enum"   => [ TestSelectEnum::One, TestSelectEnum::Two, 0, "One", "One", "Two" ],
        ];
    }


    #[DataProvider("providerHas")]
    public function testHas(Select $select, string $key, bool $expected): void {
        $this->assertSame($expected, $select->has($key));
    }

    public static function providerHas(): array {
        $filled = new Select(5, "Five", [ "extra" => "x", "empty" => "" ]);
        $empty  = new Select(0, "", [ "extra" => "" ]);

        return [
            "filled_id"      => [ $filled, "id", true ],
            "filled_value"   => [ $filled, "value", true ],
            "filled_extra"   => [ $filled, "extra", true ],
            "filled_empty"   => [ $filled, "empty", true ],
            "filled_missing" => [ $filled, "missing", false ],
            "empty_id"       => [ $empty, "id", true ],
            "empty_value"    => [ $empty, "value", true ],
            "empty_extra"    => [ $empty, "extra", true ],
        ];
    }


    #[DataProvider("providerHasValue")]
    public function testHasValue(Select $select, string $key, bool $expected): void {
        $this->assertSame($expected, $select->hasValue($key));
    }

    public static function providerHasValue(): array {
        $filled = new Select(5, "Five", [ "extra" => "x", "empty" => "" ]);
        $empty  = new Select(0, "", [ "extra" => "" ]);

        return [
            "filled_id"      => [ $filled, "id", true ],
            "filled_value"   => [ $filled, "value", true ],
            "filled_extra"   => [ $filled, "extra", true ],
            "filled_empty"   => [ $filled, "empty", false ],
            "filled_missing" => [ $filled, "missing", false ],
            "empty_id"       => [ $empty, "id", false ],
            "empty_value"    => [ $empty, "value", false ],
            "empty_extra"    => [ $empty, "extra", false ],
        ];
    }


    #[DataProvider("providerGetString")]
    public function testGetString(Select $select, string $key, string $expected): void {
        $this->assertSame($expected, $select->getString($key));
    }

    public static function providerGetString(): array {
        $select = new Select("10", "Ten", [ "count" => "7", "mixed" => 3 ]);

        return [
            "id"    => [ $select, "id", "10" ],
            "field" => [ $select, "field", "10" ],
            "value" => [ $select, "value", "Ten" ],
            "count" => [ $select, "count", "7" ],
            "mixed" => [ $select, "mixed", "3" ],
            "nope"  => [ $select, "nope", "" ],
        ];
    }


    #[DataProvider("providerGetInt")]
    public function testGetInt(Select $select, string $key, int $expected): void {
        $this->assertSame($expected, $select->getInt($key));
    }

    public static function providerGetInt(): array {
        $select = new Select("10", "Ten", [ "count" => "7", "mixed" => 3 ]);

        return [
            "id"    => [ $select, "id", 10 ],
            "field" => [ $select, "field", 10 ],
            "value" => [ $select, "value", 0 ],
            "count" => [ $select, "count", 7 ],
            "mixed" => [ $select, "mixed", 3 ],
            "nope"  => [ $select, "nope", 0 ],
        ];
    }


    #[DataProvider("providerGetDictionary")]
    public function testGetDictionary(Select $select, string $key, int $expectedTotal, ?string $expectedItemKey = null, mixed $expectedItemValue = null): void {
        $dict = $select->getDictionary($key);
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertSame($expectedTotal, $dict->getTotal());

        if ($expectedItemKey !== null) {
            $this->assertSame($expectedItemValue, $dict->get($expectedItemKey));
        }
    }

    public static function providerGetDictionary(): array {
        return [
            "existing_data" => [ new Select(1, "One", [ "data" => [ "a" => 1 ]]), "data", 1, "a", 1 ],
            "missing_key"   => [ new Select(1, "One", [ "data" => [ "a" => 1 ]]), "missing", 0 ],
            "null_data"     => [ new Select(1, "One", [ "data" => null ]), "data", 0 ],
            "empty_array"   => [ new Select(1, "One", [ "data" => [] ]), "data", 0 ],
        ];
    }


    #[DataProvider("providerGetExtras")]
    public function testGetExtras(Select $select, array $expected): void {
        $this->assertSame($expected, $select->getExtras());
    }

    public static function providerGetExtras(): array {
        $withSet = new Select(1, "One");
        $withSet->set("added", "val");

        return [
            "with_data"      => [ new Select(1, "One", [ "data" => [ "a" => 1 ]]), [ "data" => [ "a" => 1 ]]],
            "without_extras" => [ new Select(1, "One"), [] ],
            "reflect_set"    => [ $withSet, [ "added" => "val" ] ],
        ];
    }


    #[DataProvider("providerSet")]
    public function testSet(array $groups, array $expectedProperties, array $expectedStrings, array $expectedHas, array $expectedHasValue): void {
        $select = new Select(1, "One", [ "data" => [ "a" => 1 ]]);

        foreach ($groups as $group) {
            $result = $select;
            foreach ($group as [ $key, $value ]) {
                $result = $result->set($key, $value);
            }
            $this->assertSame($select, $result);
        }

        foreach ($expectedProperties as $property => $expected) {
            $this->assertSame($expected, $select->{$property});
        }
        foreach ($expectedStrings as $key => $expected) {
            $this->assertSame($expected, $select->getString($key));
        }
        foreach ($expectedHas as $key => $expected) {
            $this->assertSame($expected, $select->has($key));
        }
        foreach ($expectedHasValue as $key => $expected) {
            $this->assertSame($expected, $select->hasValue($key));
        }
    }

    public static function providerSet(): array {
        return [
            "set_existing_property" => [
                [
                    [ [ "description", "desc" ]],
                ],
                [ "description" => "desc" ],
                [],
                [],
                [],
            ],
            "set_extra" => [
                [
                    [ [ "newExtra", 123 ]],
                ],
                [],
                [ "newExtra" => "123" ],
                [ "newExtra" => true ],
                [],
            ],
            "overwrite_existing_extra" => [
                [
                    [ [ "newExtra", 123 ]],
                    [ [ "newExtra", 456 ]],
                ],
                [],
                [ "newExtra" => "456" ],
                [],
                [],
            ],
            "nullable_extra" => [
                [
                    [ [ "nullable", null ]],
                ],
                [],
                [],
                [ "nullable" => true ],
                [ "nullable" => false ],
            ],
            "chaining" => [
                [
                    [ [ "c1", "v1" ], [ "c2", "v2" ]],
                ],
                [],
                [ "c2" => "v2" ],
                [],
                [],
            ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(Select $select, array $expected): void {
        $this->assertSame($expected, $select->jsonSerialize());
    }

    public static function providerJsonSerialize(): array {
        $withDescription = new Select(2, "Two", [ "foo" => "bar" ]);
        $withDescription->description = "desc";

        return [
            "with_description" => [
                $withDescription,
                [
                    "key"         => 2,
                    "value"       => "Two",
                    "description" => "desc",
                    "foo"         => "bar",
                ],
            ],
            "without_description" => [
                new Select(3, "Three"),
                [
                    "key"   => 3,
                    "value" => "Three",
                ],
            ],
        ];
    }


    #[DataProvider("providerCreate")]
    public function testCreate(
        array $rows,
        string $keyName,
        array|string $valName,
        ?string $descName,
        array|string|null $extraKey,
        bool $useEmpty,
        bool $distinct,
        ?int $expectedCount,
        ?int $expectedMinCount,
        array $expectedItems = [],
        array $expectedIds = [],
    ): void {
        $list = Select::create($rows, $keyName, $valName, $descName, $extraKey, $useEmpty, $distinct);

        if ($expectedCount !== null) {
            $this->assertCount($expectedCount, $list);
        }
        if ($expectedMinCount !== null) {
            $this->assertGreaterThanOrEqual($expectedMinCount, count($list));
        }
        if ($expectedIds !== []) {
            $ids = array_map(fn($item) => $item->id, $list);
            $this->assertSame($expectedIds, array_values($ids));
        }
        foreach ($expectedItems as $id => $checks) {
            $item = $this->findSelectById($list, $id);
            $this->assertInstanceOf(Select::class, $item);
            if (isset($checks["field"])) {
                $this->assertSame($checks["field"], $item->field);
            }
            if (isset($checks["value"])) {
                $this->assertSame($checks["value"], $item->value);
            }
            if (isset($checks["description"])) {
                $this->assertSame($checks["description"], $item->description);
            }
            if (isset($checks["extras"])) {
                foreach ($checks["extras"] as $key => $value) {
                    $this->assertSame($value, $item->getString($key));
                }
            }
        }
    }

    public static function providerCreate(): array {
        $rows = [
            [ "id" => 1, "name" => "One", "desc" => "d1", "extra" => "e1" ],
            [ "id" => "2", "first" => "John", "last" => "Doe", "extra" => "e2" ],
            [ "id" => 1, "name" => "Duplicate", "desc" => "d1", "extra" => "e1" ],
            [ "id" => "x", "name" => "", "desc" => "dx", "extra" => "ex" ],
        ];

        return [
            "basic_create" => [
                $rows,
                "id",
                "name",
                "desc",
                "extra",
                false,
                false,
                null,
                2,
                [
                    1 => [
                        "value"       => "One",
                        "description" => "d1",
                        "extras"      => [ "extra" => "e1" ],
                    ],
                ],
            ],
            "distinct" => [
                $rows,
                "id",
                "name",
                "desc",
                "extra",
                false,
                true,
                1,
                null,
                [],
                [ 1 ],
            ],
            "use_empty" => [
                $rows,
                "id",
                "name",
                "desc",
                "extra",
                true,
                false,
                null,
                3,
            ],
            "combined_fields" => [
                $rows,
                "id",
                [ "first", "last" ],
                null,
                null,
                false,
                false,
                null,
                1,
                [
                    2 => [
                        "value" => "John - Doe",
                    ],
                ],
            ],
            "invalid_input" => [
                [ [] ],
                "",
                "name",
                null,
                null,
                false,
                false,
                0,
                null,
            ],
        ];
    }


    #[DataProvider("providerCreateFromList")]
    public function testCreateFromList(array $input, array $expectedValues): void {
        $list = Select::createFromList($input);
        $this->assertCount(count($expectedValues), $list);

        foreach ($expectedValues as $index => $expectedValue) {
            $this->assertSame($expectedValue, $list[$index]->value);
        }
    }

    public static function providerCreateFromList(): array {
        return [
            "filled" => [ [ "a", "b" ], [ "a", "b" ] ],
            "empty"  => [ [], [] ],
        ];
    }


    #[DataProvider("providerCreateFromArray")]
    public function testCreateFromArray(array $input, array $expectedValues): void {
        $list = Select::createFromArray($input);
        $this->assertCount(count($expectedValues), $list);

        foreach ($expectedValues as $index => $expectedValue) {
            $this->assertSame($expectedValue, $list[$index]->value);
        }
    }

    public static function providerCreateFromArray(): array {
        return [
            "mixed_keys"     => [ [ "k" => "v", 5 => "five" ], [ "v", "five" ] ],
            "numeric_string" => [ [ 0 => "zero", "1" => "one" ], [ "zero", "one" ] ],
            "empty"          => [ [], [], ],
        ];
    }


    #[DataProvider("providerCreateFromMap")]
    public function testCreateFromMap(Map $map, array $expectedValues): void {
        $list = Select::createFromMap($map);
        $this->assertCount(count($expectedValues), $list);

        foreach ($expectedValues as $index => $expectedValue) {
            $this->assertSame($expectedValue, $list[$index]->value);
        }
    }

    public static function providerCreateFromMap(): array {
        $map = new Map();
        $map->set(TestSelectEnum::One, "one-val");
        $map->set(TestSelectEnum::Two, 2);

        return [
            "filled" => [ $map, [ "one-val", "2" ] ],
            "empty"  => [ new Map(), [] ],
        ];
    }


    private function findSelectById(array $list, int $id): ?Select {
        foreach ($list as $item) {
            if ($item->id === $id) {
                return $item;
            }
        }
        return null;
    }
}
