<?php
namespace Tests\Enum;

use Framework\Enum\Enum;
use Framework\Enum\Map;
use Framework\Enum\IsEnum;

use PHPUnit\Framework\TestCase;

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

    public function testIsEmpty() {
        $map = new Map();
        $this->assertTrue($map->isEmpty());

        $map->set(TestMapPlainEnum::Apple, 1);
        $this->assertFalse($map->isEmpty());
    }

    public function testIsNotEmpty() {
        $map = new Map();
        $this->assertFalse($map->isNotEmpty());

        $map->set(TestMapPlainEnum::Apple, 1);
        $this->assertTrue($map->isNotEmpty());
    }

    public function testSet() {
        $map = new Map();
        $map->set(TestMapPlainEnum::Apple, "value");

        // set should register the key (we check via has)
        $this->assertTrue($map->has(TestMapPlainEnum::Apple));

        // set should overwrite existing value
        $map->set(TestMapPlainEnum::Apple, "new value");
        $this->assertSame("new value", $map->get(TestMapPlainEnum::Apple));

        // set should allow different keys
        $map->set(TestMapPlainEnum::Banana, "banana value");
        $this->assertSame("banana value", $map->get(TestMapPlainEnum::Banana));

        // set should allow different enum types
        $map->set(TestMapBackedEnum::Red, "red value");
        $this->assertSame("red value", $map->get(TestMapBackedEnum::Red));

        // set should allow null values
        $map->set(TestMapPlainEnum::None, null);
        $this->assertTrue($map->has(TestMapPlainEnum::None));
        $this->assertNull($map->get(TestMapPlainEnum::None));
    }

    public function testHas() {
        $map = new Map();
        $this->assertFalse($map->has(TestMapPlainEnum::Apple));

        $map->set(TestMapPlainEnum::Apple, 123);
        $this->assertTrue($map->has(TestMapPlainEnum::Apple));
        $this->assertFalse($map->has(TestMapPlainEnum::Banana));

        // has should work for different enum types
        $this->assertFalse($map->has(TestMapBackedEnum::Red));
        $map->set(TestMapBackedEnum::Red, "red");
        $this->assertTrue($map->has(TestMapBackedEnum::Red));
    }

    public function testGet() {
        $map = new Map();
        $this->assertNull($map->get(TestMapPlainEnum::Apple));

        $map->set(TestMapPlainEnum::Apple, "x");
        $this->assertSame("x", $map->get(TestMapPlainEnum::Apple));

        // get should work for different enum types
        $this->assertNull($map->get(TestMapBackedEnum::Red));
        $map->set(TestMapBackedEnum::Red, "red");
        $this->assertSame("red", $map->get(TestMapBackedEnum::Red));
    }

    public function testGetInt() {
        $map = new Map();
        $this->assertSame(0, $map->getInt(TestMapPlainEnum::Apple));

        // getInt should return the integer value if it's already an integer
        $map->set(TestMapPlainEnum::Apple, 42);
        $this->assertSame(42, $map->getInt(TestMapPlainEnum::Apple));

        // getInt should convert numeric strings to integers
        $map->set(TestMapPlainEnum::Apple, "42");
        $this->assertSame(42, $map->getInt(TestMapPlainEnum::Apple));

        // getInt should truncate floats to integers
        $map->set(TestMapPlainEnum::Banana, 7.9);
        $this->assertSame(8, $map->getInt(TestMapPlainEnum::Banana));

        // getInt should work for different enum types
        $this->assertSame(0, $map->getInt(TestMapBackedEnum::Red));
        $map->set(TestMapBackedEnum::Red, "100");
        $this->assertSame(100, $map->getInt(TestMapBackedEnum::Red));

        // getInt should return 0 for non-numeric values
        $map->set(TestMapPlainEnum::None, "not a number");
        $this->assertSame(0, $map->getInt(TestMapPlainEnum::None));
    }

    public function testGetString() {
        $map = new Map();
        $this->assertSame("", $map->getString(TestMapPlainEnum::Apple));

        // getString should return the string value if it's already a string
        $map->set(TestMapPlainEnum::Apple, "hello");
        $this->assertSame("hello", $map->getString(TestMapPlainEnum::Apple));

        // getString should convert other types to strings
        $map->set(TestMapPlainEnum::Apple, 55);
        $this->assertSame("55", $map->getString(TestMapPlainEnum::Apple));

        // getString should work for different enum types
        $this->assertSame("", $map->getString(TestMapBackedEnum::Red));
        $map->set(TestMapBackedEnum::Red, "red value");
        $this->assertSame("red value", $map->getString(TestMapBackedEnum::Red));

        // getString should return empty string for null values
        $map->set(TestMapPlainEnum::None, null);
        $this->assertSame("", $map->getString(TestMapPlainEnum::None));
    }

    public function testCount() {
        $map = new Map();
        $this->assertSame(0, $map->count());

        $map->set(TestMapPlainEnum::Apple, 1);
        $map->set(TestMapPlainEnum::Banana, 2);
        $this->assertSame(2, $map->count());
    }

    public function testGetIterator() {
        $map = new Map();
        $map->set(TestMapPlainEnum::Apple, "a");
        $map->set(TestMapPlainEnum::Banana, "b");

        $collected = [];
        foreach ($map->getIterator() as $key => $value) {
            $collected[$key->toString()] = $value;
        }

        $this->assertSame([ "Apple" => "a", "Banana" => "b" ], $collected);
    }

    public function testJsonSerialize() {
        $map = new Map();
        $map->set(TestMapPlainEnum::Apple, "a");
        $map->set(TestMapBackedEnum::Red, "r");

        $serialized = $map->jsonSerialize();

        // keys should be the enum string representations
        $this->assertArrayHasKey('Apple', $serialized);
        $this->assertArrayHasKey('red', $serialized);
        $this->assertSame("a", $serialized["Apple"]);
        $this->assertSame("r", $serialized["red"]);
    }
}
