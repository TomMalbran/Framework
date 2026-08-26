<?php
namespace Tests\Enum;

use Framework\IO\Request;
use Framework\Enum\Enum;
use Framework\Enum\IsEnum;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

enum TestPlainEnum implements Enum {
    use IsEnum;

    case None;
    case Apple;
    case Banana;
    case All;
}

enum TestBackedEnum: string implements Enum {
    use IsEnum;

    case None  = "";
    case Red   = "red";
    case Green = "green";
    case All   = "all";
}

class EnumTest extends TestCase {

    #[DataProvider("providerFromValue")]
    public function testFromValue(string $enumClass, mixed $input, Enum $expected): void {
        $this->assertSame($expected, $enumClass::fromValue($input));
    }

    public static function providerFromValue(): array {
        return [
            "plain name"           => [ TestPlainEnum::class, "Apple", TestPlainEnum::Apple ],
            "plain name lowercase" => [ TestPlainEnum::class, "apple", TestPlainEnum::Apple ],
            "plain enum instance"  => [ TestPlainEnum::class, TestPlainEnum::Banana, TestPlainEnum::Banana ],
            "plain invalid"        => [ TestPlainEnum::class, "Unknown", TestPlainEnum::None ],
            "backed name"          => [ TestBackedEnum::class, "Red", TestBackedEnum::Red ],
            "backed value"         => [ TestBackedEnum::class, "red", TestBackedEnum::Red ],
            "backed enum instance" => [ TestBackedEnum::class, TestBackedEnum::Red, TestBackedEnum::Red ],
            "backed invalid"       => [ TestBackedEnum::class, "x", TestBackedEnum::None ],
            "mixed enums"          => [ TestBackedEnum::class, TestPlainEnum::All, TestBackedEnum::All ],
        ];
    }


    #[DataProvider("providerFromList")]
    public function testFromList(string $enumClass, mixed $input, array $expected): void {
        $this->assertSame($expected, $enumClass::fromList($input));
    }

    public static function providerFromList(): array {
        return [
            "plain list"             => [
                TestPlainEnum::class,
                [ "Apple", "Banana" ],
                [ TestPlainEnum::Apple, TestPlainEnum::Banana ],
            ],
            "backed list"            => [
                TestBackedEnum::class,
                [ "red", "green" ],
                [ TestBackedEnum::Red, TestBackedEnum::Green ],
            ],
            "plain mixed with enums" => [
                TestPlainEnum::class,
                [ TestPlainEnum::Apple, "Unknown", TestPlainEnum::Banana ],
                [ TestPlainEnum::Apple, TestPlainEnum::None, TestPlainEnum::Banana ],
            ],
            "plain invalid in list"  => [
                TestPlainEnum::class,
                [ "Apple", "Unknown", "Banana" ],
                [ TestPlainEnum::Apple, TestPlainEnum::None, TestPlainEnum::Banana ],
            ],
            "single string"          => [
                TestPlainEnum::class,
                "Apple",
                [ TestPlainEnum::Apple ],
            ],
            "single enum"            => [
                TestPlainEnum::class,
                TestPlainEnum::Banana,
                [ TestPlainEnum::Banana ],
            ],
            "empty string"           => [
                TestPlainEnum::class,
                "",
                [],
            ],
            "null"                   => [
                TestPlainEnum::class,
                null,
                [],
            ],
        ];
    }


    #[DataProvider("providerIsValid")]
    public function testIsValid(string $enumClass, mixed $input, bool $expected): void {
        $this->assertSame($expected, $enumClass::isValid($input));
    }

    public static function providerIsValid(): array {
        return [
            "plain valid"           => [ TestPlainEnum::class, "Apple", true ],
            "plain valid lowercase" => [ TestPlainEnum::class, "apple", true ],
            "plain invalid"         => [ TestPlainEnum::class, "Nope", false ],
            "backed valid value"    => [ TestBackedEnum::class, "green", true ],
            "backed valid name"     => [ TestBackedEnum::class, "Red", true ],
            "backed invalid"        => [ TestBackedEnum::class, "blue", false ],
        ];
    }


    #[DataProvider("providerGetAll")]
    public function testGetAll(string $enumClass, Enum $excluded, Enum $included): void {
        $all = $enumClass::getAll();
        $this->assertNotContains($excluded, $all);
        $this->assertContains($included, $all);
    }

    public static function providerGetAll(): array {
        return [
            "plain"  => [ TestPlainEnum::class, TestPlainEnum::None, TestPlainEnum::Apple ],
            "backed" => [ TestBackedEnum::class, TestBackedEnum::None, TestBackedEnum::Red ],
        ];
    }


    #[DataProvider("providerGetNames")]
    public function testGetNames(string $enumClass, array $expectedIncluded): void {
        $names = $enumClass::getNames();
        foreach ($expectedIncluded as $value) {
            $this->assertContains($value, $names);
        }
    }

    public static function providerGetNames(): array {
        return [
            "plain"  => [ TestPlainEnum::class, [ "Apple", "Banana" ] ],
            "backed" => [ TestBackedEnum::class, [ "red", "green" ] ],
        ];
    }


    #[DataProvider("providerContains")]
    public function testContains(string $enumClass, array $values, Enum $value, bool $expected): void {
        $this->assertSame($expected, $enumClass::contains($values, $value));
    }

    public static function providerContains(): array {
        return [
            "plain contained"      => [
                TestPlainEnum::class,
                [ TestPlainEnum::Apple, TestPlainEnum::Banana ],
                TestPlainEnum::Apple,
                true,
            ],
            "plain empty list"     => [
                TestPlainEnum::class,
                [],
                TestPlainEnum::Apple,
                false,
            ],
            "backed contained"     => [
                TestBackedEnum::class,
                [ TestBackedEnum::Red ],
                TestBackedEnum::Red,
                true,
            ],
            "backed not contained" => [
                TestBackedEnum::class,
                [ TestBackedEnum::Green ],
                TestBackedEnum::Red,
                false,
            ],
            "plain none"           => [
                TestPlainEnum::class,
                [ TestPlainEnum::Apple ],
                TestPlainEnum::None,
                false,
            ],
            "backed none"          => [
                TestBackedEnum::class,
                [ TestBackedEnum::Red ],
                TestBackedEnum::None,
                false,
            ],
        ];
    }


    #[DataProvider("providerToString")]
    public function testToString(Enum $input, string $expected): void {
        $this->assertSame($expected, $input->toString());
    }

    public static function providerToString(): array {
        return [
            "plain"  => [ TestPlainEnum::Apple, "Apple" ],
            "backed" => [ TestBackedEnum::Red, "red" ],
            "none"   => [ TestPlainEnum::None, "" ],
        ];
    }


    #[DataProvider("providerJsonSerialize")]
    public function testJsonSerialize(Enum $input, string $expected): void {
        $this->assertSame($expected, $input->jsonSerialize());
    }

    public static function providerJsonSerialize(): array {
        return [
            "plain"  => [ TestPlainEnum::Apple, "Apple" ],
            "backed" => [ TestBackedEnum::Red, "red" ],
            "none"   => [ TestPlainEnum::None, "" ],
        ];
    }
}
