<?php
namespace Tests\IO;

use Framework\IO\Select;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;
use Framework\Enum\Map;
use Framework\Utils\Dictionary;

use PHPUnit\Framework\TestCase;

enum TestSelectEnum implements Enum {
    use IsEnum;

    case None;
    case One;
    case Two;
}

class SelectTest extends TestCase {

    public function testConstruct() {
        $s = new Select(1, "One");
        $this->assertSame(1, $s->id);
        $this->assertSame("1", $s->field);
        $this->assertSame(1, $s->key);
        $this->assertSame("One", $s->value);
        $this->assertSame([], $s->getExtras());

        // enum keys/values should be converted to strings for field/value, and id cast to int
        $s2 = new Select(TestSelectEnum::One, TestSelectEnum::Two);
        $this->assertSame("One", $s2->field);
        $this->assertSame("Two", $s2->value);
    }

    public function testHas() {
        $s = new Select(5, "Five", [ "extra" => "x", "empty" => "" ]);
        $this->assertTrue($s->has("id"));
        $this->assertTrue($s->has("value"));
        $this->assertTrue($s->has("extra"));
        $this->assertTrue($s->has("empty"));
        $this->assertFalse($s->has("missing"));

        // even if the property exists, has should return false if it's empty
        $s2 = new Select(0, "", [ "extra" => "" ]);
        $this->assertTrue($s2->has("id"));
        $this->assertTrue($s2->has("value"));
        $this->assertTrue($s2->has("extra"));
    }

    public function testHasValue() {
        $s = new Select(5, "Five", [ "extra" => "x", "empty" => "" ]);
        $this->assertTrue($s->hasValue("id"));
        $this->assertTrue($s->hasValue("value"));
        $this->assertTrue($s->hasValue("extra"));
        $this->assertFalse($s->hasValue("empty"));
        $this->assertFalse($s->hasValue("missing"));

        // even if the property exists, if it's empty it should return false
        $s2 = new Select(0, "", [ "extra" => "" ]);
        $this->assertFalse($s2->hasValue("id"));
        $this->assertFalse($s2->hasValue("value"));
        $this->assertFalse($s2->hasValue("extra"));
    }

    public function testGetString() {
        $s = new Select("10", "Ten", [ "count" => "7", "mixed" => 3 ]);
        $this->assertSame("10", $s->getString("id"));
        $this->assertSame("10", $s->getString("field"));
        $this->assertSame("Ten", $s->getString("value"));
        $this->assertSame("7", $s->getString("count"));
        $this->assertSame("3", $s->getString("mixed"));
        $this->assertSame("", $s->getString("nope"));
    }

    public function testGetInt() {
        $s = new Select("10", "Ten", [ "count" => "7", "mixed" => 3 ]);
        $this->assertSame(10, $s->getInt("id"));
        $this->assertSame(10, $s->getInt("field"));
        $this->assertSame(0, $s->getInt("value"));
        $this->assertSame(7, $s->getInt("count"));
        $this->assertSame(3, $s->getInt("mixed"));
        $this->assertSame(0, $s->getInt("nope"));
    }

    public function testGetDictionary() {
        $s = new Select(1, "One", [ "data" => [ "a" => 1 ]]);
        $dict = $s->getDictionary("data");
        $this->assertInstanceOf(Dictionary::class, $dict);
        $this->assertSame(1, $dict->get("a"));

        // missing key should return empty dictionary
        $emptyDict = $s->getDictionary("missing");
        $this->assertInstanceOf(Dictionary::class, $emptyDict);
        $this->assertSame(0, $emptyDict->getTotal());

        // when extras has null the dictionary should be empty
        $sNull = new Select(1, "One", [ "data" => null ]);
        $nullDict = $sNull->getDictionary("data");
        $this->assertInstanceOf(Dictionary::class, $nullDict);
        $this->assertSame(0, $nullDict->getTotal());

        // when extras has an empty array the dictionary should be empty
        $sEmpty = new Select(1, "One", [ "data" => [] ]);
        $emptyArrDict = $sEmpty->getDictionary("data");
        $this->assertInstanceOf(Dictionary::class, $emptyArrDict);
        $this->assertSame(0, $emptyArrDict->getTotal());
    }

    public function testGetExtras() {
        $s = new Select(1, "One", [ "data" => [ "a" => 1 ]]);
        $this->assertSame(["data" => [ "a" => 1 ]], $s->getExtras());

        // when no extras were provided it should return an empty array
        $sNo = new Select(1, "One");
        $this->assertSame([], $sNo->getExtras());

        // extras should reflect values set via set()
        $sNo->set("added", "val");
        $this->assertSame([ "added" => "val" ], $sNo->getExtras());
    }

    public function testSet() {
        $s = new Select(1, "One", [ "data" => [ "a" => 1 ]]);

        // set existing property
        $s->set("description", "desc");
        $this->assertSame("desc", $s->description);

        // set extra
        $s->set("newExtra", 123);
        $this->assertTrue($s->has("newExtra"));
        $this->assertSame("123", $s->getString("newExtra"));

        // overwrite existing extra
        $s->set("newExtra", 456);
        $this->assertSame("456", $s->getString("newExtra"));

        // set extra to null should be present but hasValue should be false
        $s->set("nullable", null);
        $this->assertTrue($s->has("nullable"));
        $this->assertFalse($s->hasValue("nullable"));

        // chaining should work
        $s->set("c1", "v1")->set("c2", "v2");
        $this->assertSame("v2", $s->getString("c2"));
    }

    public function testJsonSerialize() {
        $s = new Select(2, "Two", [ "foo" => "bar" ]);
        $s->description = "desc";
        $serialized = $s->jsonSerialize();
        $this->assertArrayHasKey("key", $serialized);
        $this->assertArrayHasKey("value", $serialized);
        $this->assertArrayHasKey("description", $serialized);
        $this->assertArrayHasKey("foo", $serialized);
        $this->assertSame(2, $serialized["key"]);
        $this->assertSame("Two", $serialized["value"]);
        $this->assertSame("desc", $serialized["description"]);
        $this->assertSame("bar", $serialized["foo"]);

        // when description is empty it should not appear
        $s2 = new Select(3, "Three");
        $serialized2 = $s2->jsonSerialize();
        $this->assertArrayNotHasKey("description", $serialized2);
    }

    public function testCreate() {
        $rows = [
            [ "id" => 1, "name" => "One", "desc" => "d1", "extra" => "e1" ],
            [ "id" => "2", "first" => "John", "last" => "Doe", "extra" => "e2" ],
            [ "id" => 1, "name" => "Duplicate", "desc" => "d1", "extra" => "e1" ],
            [ "id" => "x", "name" => "", "desc" => "dx", "extra" => "ex" ],
        ];

        // basic create: use name as valName, include description and extra
        $list = Select::create($rows, "id", "name", "desc", "extra", useEmpty: false, distinct: false);
        // row with id 'x' has empty name and useEmpty=false so it should be skipped
        $this->assertGreaterThanOrEqual(2, count($list));
        $first = $list[0];
        $this->assertInstanceOf(Select::class, $first);
        $this->assertSame(1, $first->id);
        $this->assertSame("One", $first->value);
        $this->assertSame("d1", $first->description);
        $this->assertSame("e1", $first->getString("extra"));

        // distinct true should remove duplicate id=1 second occurrence
        $distinct = Select::create($rows, "id", "name", "desc", "extra", useEmpty: false, distinct: true);
        $ids = array_map(fn($it) => $it->id, $distinct);
        $this->assertSame(1, count($distinct));
        $this->assertSame([ 1 ], array_values($ids));

        // useEmpty true should include empty name rows
        $withEmpty = Select::create($rows, "id", "name", "desc", "extra", useEmpty: true, distinct: false);
        $this->assertGreaterThanOrEqual(3, count($withEmpty));

        // test valName as array combining fields (first + last)
        $combined = Select::create($rows, "id", ["first", "last"], null, null, useEmpty: false, distinct: false);
        // second row should produce "John - Doe"
        $found = false;
        foreach ($combined as $it) {
            if ($it->id === 2) {
                $this->assertSame("John - Doe", $it->value);
                $found = true;
            }
        }
        $this->assertTrue($found, "combined name not found for id=2");

        // test with invalid keys/values, should be skipped
        $invalid = Select::create([ [] ], "", "name");
        $this->assertCount(0, $invalid);
    }

    public function testCreateFromList() {
        $list = Select::createFromList([ "a", "b" ]);
        $this->assertCount(2, $list);
        $this->assertSame("a", $list[0]->value);
        $this->assertSame("b", $list[1]->value);

        // empty list should return an empty array
        $empty = Select::createFromList([]);
        $this->assertSame([], $empty);
    }

    public function testCreateFromArray() {
        $arr = Select::createFromArray([ "k" => "v", 5 => "five" ]);
        $this->assertCount(2, $arr);
        $this->assertSame("v", $arr[0]->value);
        $this->assertSame("five", $arr[1]->value);

        // empty array should return an empty array
        $emptyArr = Select::createFromArray([]);
        $this->assertSame([], $emptyArr);

        // numeric and string keys preserve values order
        $numericKeys = Select::createFromArray([ 0 => "zero", "1" => "one" ]);
        $this->assertCount(2, $numericKeys);
        $this->assertSame("zero", $numericKeys[0]->value);
        $this->assertSame("one", $numericKeys[1]->value);
    }

    public function testCreateFromMap() {
        // createFromMap using an Enum Map
        $map = new Map();
        $map->set(TestSelectEnum::One, "one-val");
        $map->set(TestSelectEnum::Two, 2);
        $fromMap = Select::createFromMap($map);
        $this->assertCount(2, $fromMap);

        // keys should be enum values as provided
        $this->assertSame("one-val", $fromMap[0]->value);
        $this->assertSame("2", $fromMap[1]->value);

        // empty map should return an empty array
        $emptyMap = new Map();
        $fromEmpty = Select::createFromMap($emptyMap);
        $this->assertSame([], $fromEmpty);
    }
}
