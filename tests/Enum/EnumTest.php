<?php
namespace Tests\Enum;

use Framework\IO\Request;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use PHPUnit\Framework\TestCase;

enum TestPlainEnum implements Enum {
    use IsEnum;

    case None;
    case Apple;
    case Banana;
}

enum TestBackedEnum: string implements Enum {
    use IsEnum;

    case None  = "";
    case Red   = "red";
    case Green = "green";
}

class EnumTest extends TestCase {

    public function testFromValue() {
        // plain enum should match case name
        $this->assertSame(TestPlainEnum::Apple, TestPlainEnum::fromValue("Apple"));
        $this->assertSame(TestPlainEnum::Apple, TestPlainEnum::fromValue("apple"));
        $this->assertSame(TestPlainEnum::Banana, TestPlainEnum::fromValue(TestPlainEnum::Banana));
        $this->assertSame(TestPlainEnum::None, TestPlainEnum::fromValue("Unknown"));

        // backed enum should match case value
        $this->assertSame(TestBackedEnum::Red, TestBackedEnum::fromValue("Red"));
        $this->assertSame(TestBackedEnum::Red, TestBackedEnum::fromValue("red"));
        $this->assertSame(TestBackedEnum::Red, TestBackedEnum::fromValue(TestBackedEnum::Red));
        $this->assertSame(TestBackedEnum::None, TestBackedEnum::fromValue("x"));
    }

    public function testFromRequest() {
        // plain enum should match case name
        $req = new Request([ "fruit" => "Banana" ]);
        $this->assertSame(TestPlainEnum::Banana, TestPlainEnum::fromRequest($req, "fruit"));

        // backed enum should match case value
        $req2 = new Request([ "color" => "green" ]);
        $this->assertSame(TestBackedEnum::Green, TestBackedEnum::fromRequest($req2, "color"));

        // missing key should return default
        $this->assertSame(TestPlainEnum::None, TestPlainEnum::fromRequest($req, "missing"));
        $this->assertSame(TestBackedEnum::None, TestBackedEnum::fromRequest($req2, "missing"));
    }

    public function testFromList() {
        // plain enum should match case name
        $list = TestPlainEnum::fromList([ "Apple", "Banana" ]);
        $this->assertSame([ TestPlainEnum::Apple, TestPlainEnum::Banana ], $list);

        // backed enum should match case value
        $list2 = TestBackedEnum::fromList([ "red", "green" ]);
        $this->assertSame([ TestBackedEnum::Red, TestBackedEnum::Green ], $list2);

        // with Enum instances in the list, they should be returned as-is
        $list3 = TestPlainEnum::fromList([ TestPlainEnum::Apple, "Unknown", TestPlainEnum::Banana ]);
        $this->assertSame([ TestPlainEnum::Apple, TestPlainEnum::None, TestPlainEnum::Banana ], $list3);

        // invalid values are converted to None
        $list4 = TestPlainEnum::fromList([ "Apple", "Unknown", "Banana" ]);
        $this->assertSame([ TestPlainEnum::Apple, TestPlainEnum::None, TestPlainEnum::Banana ], $list4);

        // it works with non-array input by treating it as a single value
        $list5 = TestPlainEnum::fromList("Apple");
        $this->assertSame([ TestPlainEnum::Apple ], $list5);

        // with a single enum
        $list6 = TestPlainEnum::fromList(TestPlainEnum::Banana);
        $this->assertSame([ TestPlainEnum::Banana ], $list6);

        // empty string should return empty list
        $this->assertSame([], TestPlainEnum::fromList(""));

        // null should return empty list
        $this->assertSame([], TestPlainEnum::fromList(null));
    }

    public function testIsValid() {
        // plain enum should match case name
        $this->assertTrue(TestPlainEnum::isValid("Apple"));
        $this->assertTrue(TestPlainEnum::isValid("apple"));
        $this->assertFalse(TestPlainEnum::isValid("Nope"));

        // backed enum should match case value
        $this->assertTrue(TestBackedEnum::isValid("green"));
        $this->assertTrue(TestBackedEnum::isValid("Red"));
        $this->assertFalse(TestBackedEnum::isValid("blue"));
    }

    public function testGetAll() {
        // getAll should return all cases except None
        $all = TestPlainEnum::getAll();
        $this->assertNotContains(TestPlainEnum::None, $all);
        $this->assertContains(TestPlainEnum::Apple, $all);

        // backed enum getAll should also return all cases except None
        $allBacked = TestBackedEnum::getAll();
        $this->assertNotContains(TestBackedEnum::None, $allBacked);
        $this->assertContains(TestBackedEnum::Red, $allBacked);
    }

    public function testGetNames() {
        // getNames should return the case names for plain enum
        $names = TestPlainEnum::getNames();
        $this->assertContains("Apple", $names);
        $this->assertContains("Banana", $names);

        // getNames for backed enum should return the case values
        $backedNames = TestBackedEnum::getNames();
        $this->assertContains("red", $backedNames);
        $this->assertContains("green", $backedNames);
    }

    public function testContains() {
        // contains should check if a value is in a list of cases
        $this->assertTrue(TestPlainEnum::contains([ TestPlainEnum::Apple, TestPlainEnum::Banana ], TestPlainEnum::Apple));

        // contains should return false if value is not in list
        $this->assertFalse(TestPlainEnum::contains([], TestPlainEnum::Apple));

        // contains should work with backed enums too
        $this->assertTrue(TestBackedEnum::contains([ TestBackedEnum::Red ], TestBackedEnum::Red));
        $this->assertFalse(TestBackedEnum::contains([ TestBackedEnum::Green ], TestBackedEnum::Red));

        // contains should return false if value is None
        $this->assertFalse(TestPlainEnum::contains([ TestPlainEnum::Apple ], TestPlainEnum::None));
        $this->assertFalse(TestBackedEnum::contains([ TestBackedEnum::Red ], TestBackedEnum::None));
    }

    public function testToString() {
        // toString should return case name for plain enum
        $this->assertSame("Apple", TestPlainEnum::Apple->toString());

        // toString should return case value for backed enum
        $this->assertSame("red", TestBackedEnum::Red->toString());

        // None case should return empty string
        $this->assertSame("", TestPlainEnum::None->toString());
    }

    public function testJsonSerialize() {
        // jsonSerialize should return same as toString
        $this->assertSame("Apple", TestPlainEnum::Apple->jsonSerialize());

        // jsonSerialize for backed enum should return case value
        $this->assertSame("red", TestBackedEnum::Red->jsonSerialize());

        // None case should return empty string
        $this->assertSame("", TestPlainEnum::None->jsonSerialize());
    }
}
