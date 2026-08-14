<?php
namespace Tests\Database;

use Framework\Core\VariableType;
use Framework\Date\Date;
use Framework\Date\Type\DateFormat;
use Framework\File\File;
use Framework\Utils\Dictionary;

use Tests\Database\Fixture\CrateEntity;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Entity, which reads a database row into the properties of a class
 */
class EntityTest extends TestCase {

    /**
     * Returns an Entity built from the given row
     * @param array<string,mixed> $data Optional.
     * @return CrateEntity
     */
    private function entity(array $data = []): CrateEntity {
        return new CrateEntity(new Dictionary($data));
    }



    /**
     * A column read into a property of the type the property declares
     * @param string $key
     * @param mixed  $given
     * @param mixed  $expected
     * @return void
     */
    #[DataProvider("providerValues")]
    public function testTheValueIsCastToTheTypeOfTheProperty(
        string $key,
        mixed $given,
        mixed $expected,
    ): void {
        $this->assertSame($expected, $this->entity([ $key => $given ])->get($key));
    }

    /**
     * A row comes back from the database as strings, so every one of these
     * is the column arriving as the text of its value
     * @return array<string,array{string,mixed,mixed}>
     */
    public static function providerValues(): array {
        return [
            "a string"          => [ "name", "Crate", "Crate" ],
            "a number"          => [ "amount", "12", 12 ],
            "a number as a int" => [ "amount", 12, 12 ],
            "a float"           => [ "weight", "1.5", 1.5 ],
            "a boolean"         => [ "isOpen", "1", true ],
            "a boolean that is" => [ "isOpen", "0", false ],
            "an array"          => [ "tags", [ "one" => 1 ], [ "one" => 1 ] ],
            "an array that is"  => [ "tags", [], [] ],
        ];
    }

    /**
     * A column the Entity wraps in an object rather than casting
     * @param string $key
     * @param mixed  $given
     * @param string $expected The type the Entity reports for it
     * @param string $class    The class it is actually built as
     * @return void
     */
    #[DataProvider("providerTypes")]
    public function testTheValueIsWrappedInTheTypeOfTheProperty(
        string $key,
        mixed $given,
        string $expected,
        string $class,
    ): void {
        $entity = $this->entity([ $key => $given ]);

        $this->assertSame($expected, $entity->getType($key));
        $this->assertInstanceOf($class, $entity->get($key));
    }

    /**
     * getType names the three classes the Entity reads a column into and the
     * Enum it was given, and falls back to gettype for anything else, which
     * is why a Dictionary only comes back as an object
     * @return array<string,array{string,mixed,string,string}>
     */
    public static function providerTypes(): array {
        return [
            "a date"       => [ "sentTime", 1600000000, Date::class, Date::class ],
            "a dictionary" => [ "extra", [ "one" => 1 ], "object", Dictionary::class ],
            "a file"       => [ "label", "crate.png", File::class, File::class ],
            "an enum"      => [ "kind", "String", VariableType::class, VariableType::class ],
        ];
    }

    public function testAKeyThatIsNotThereHasNoType(): void {
        $this->assertSame("", $this->entity([])->getType("nothing"));
    }

    public function testTheDateIsBuiltFromItsTimestamp(): void {
        $entity = $this->entity([ "sentTime" => 1600000000 ]);

        $this->assertNotNull($entity->sentTime);
        $this->assertSame("2020-09-13 14:26:40", $entity->sentTime->toString(DateFormat::ReverseSeconds));
    }

    public function testTheDictionaryKeepsWhatTheColumnHeld(): void {
        $entity = $this->entity([ "extra" => [ "colour" => "red", "size" => 3 ] ]);

        $this->assertNotNull($entity->extra);
        $this->assertSame("red", $entity->extra->getString("colour"));
        $this->assertSame(3, $entity->extra->getInt("size"));
    }

    public function testTheFileKeepsThePathItWasGiven(): void {
        $entity = $this->entity([ "label" => "crates/one.png" ]);

        $this->assertNotNull($entity->label);
        $this->assertSame("crates/one.png", $entity->label->toString());
    }

    public function testTheEnumIsBuiltFromItsName(): void {
        $this->assertSame(VariableType::String, $this->entity([ "kind" => "String" ])->kind);
    }

    public function testAValueTheEnumDoesNotKnowFallsBackToNone(): void {
        $this->assertSame(VariableType::None, $this->entity([ "kind" => "notAType" ])->kind);
    }

    public function testAColumnThatIsNotThereLeavesTheDefault(): void {
        $entity = $this->entity([ "name" => "Crate" ]);

        $this->assertSame(0, $entity->amount);
        $this->assertNull($entity->sentTime);
        $this->assertSame(VariableType::None, $entity->kind);
    }



    /**
     * A row, and whether the Entity built from it counts as one that exists
     * @param array<string,mixed> $data
     * @param bool                $expected
     * @return void
     */
    #[DataProvider("providerExists")]
    public function testAnEntityExistsOnceAColumnHasBeenRead(array $data, bool $expected): void {
        $entity = $this->entity($data);

        $this->assertSame($expected, $entity->exists());
        $this->assertSame(!$expected, $entity->isEmpty());
    }

    /**
     * A key the Entity has no property for never makes it one that exists
     * @return array<string,array{array<string,mixed>,bool}>
     */
    public static function providerExists(): array {
        return [
            "nothing read"      => [ [], false ],
            "one column"        => [ [ "name" => "Crate" ], true ],
            "an empty column"   => [ [ "name" => "" ], true ],
            "a column it lacks" => [ [ "notAColumn" => "Crate" ], false ],
            "one of each"       => [ [ "notAColumn" => 1, "amount" => 2 ], true ],
        ];
    }

    /**
     * A key, and whether the Entity declares a property for it
     * @param string $key
     * @param bool   $expected
     * @return void
     */
    #[DataProvider("providerHas")]
    public function testTheEntityKnowsWhichKeysItHolds(string $key, bool $expected): void {
        $this->assertSame($expected, $this->entity()->has($key));
    }

    /**
     * isEmpty is protected on the Entity rather than declared by the class,
     * and the Entity walks the protected properties too
     * @return array<string,array{string,bool}>
     */
    public static function providerHas(): array {
        return [
            "a column"       => [ "name", true ],
            "another column" => [ "sentTime", true ],
            "one it lacks"   => [ "notAColumn", false ],
            "nothing"        => [ "", false ],
            "the empty flag" => [ "isEmpty", true ],
        ];
    }

    /**
     * A key, and whether the value the Entity read into it is worth anything
     * @param string $key
     * @param mixed  $given
     * @param bool   $expected
     * @return void
     */
    #[DataProvider("providerHasValue")]
    public function testTheEntityKnowsWhichValuesAreEmpty(
        string $key,
        mixed $given,
        bool $expected,
    ): void {
        $this->assertSame($expected, $this->entity([ $key => $given ])->hasValue($key));
    }

    /**
     * @return array<string,array{string,mixed,bool}>
     */
    public static function providerHasValue(): array {
        return [
            "a string"        => [ "name", "Crate", true ],
            "an empty string" => [ "name", "", false ],
            "a number"        => [ "amount", 12, true ],
            "a zero"          => [ "amount", 0, false ],
            "an array"        => [ "tags", [ "one" => 1 ], true ],
            "an empty array"  => [ "tags", [], false ],
            "a key it lacks"  => [ "notAColumn", "Crate", false ],
        ];
    }

    /**
     * A key that has no property, and the default handed back for it
     * @param string $method
     * @param mixed  $default
     * @param mixed  $expected
     * @return void
     */
    #[DataProvider("providerDefaults")]
    public function testAKeyThatIsNotThereHandsBackTheDefault(
        string $method,
        mixed $default,
        mixed $expected,
    ): void {
        $entity = $this->entity([ "name" => "Crate" ]);

        /** @var callable */
        $callable = [ $entity, $method ];
        $this->assertSame($expected, $callable("notAColumn", $default));
    }

    /**
     * @return array<string,array{string,mixed,mixed}>
     */
    public static function providerDefaults(): array {
        return [
            "a value"   => [ "get", "none", "none" ],
            "no value"  => [ "get", null, null ],
            "a string"  => [ "getString", "none", "none" ],
            "no string" => [ "getString", "", "" ],
            "a number"  => [ "getInt", 7, 7 ],
            "no number" => [ "getInt", 0, 0 ],
        ];
    }

    /**
     * A key read back through the accessor that names a type
     * @param string $method
     * @param string $key
     * @param mixed  $expected
     * @return void
     */
    #[DataProvider("providerAccessors")]
    public function testTheAccessorGivesTheValueAsItsType(
        string $method,
        string $key,
        mixed $expected,
    ): void {
        $entity = $this->entity([
            "name"   => "Crate",
            "amount" => 12,
            "kind"   => "String",
        ]);

        /** @var callable */
        $callable = [ $entity, $method ];
        $this->assertSame($expected, $callable($key));
    }

    /**
     * getString goes through Strings::toString, which knows a number and an
     * Enum, and getInt through Numbers::toInt, which knows the text of one
     * @return array<string,array{string,string,mixed}>
     */
    public static function providerAccessors(): array {
        return [
            "a string"           => [ "getString", "name", "Crate" ],
            "a number as text"   => [ "getString", "amount", "12" ],
            "an enum as text"    => [ "getString", "kind", "String" ],
            "a number"           => [ "getInt", "amount", 12 ],
            "a string as number" => [ "getInt", "name", 0 ],
        ];
    }



    /**
     * A key handed to set(), and whether the Entity took it
     * @param string $key
     * @param bool   $expected
     * @return void
     */
    #[DataProvider("providerSet")]
    public function testOnlyAKeyTheEntityHoldsCanBeSet(string $key, bool $expected): void {
        $entity = $this->entity();

        $this->assertSame($expected, $entity->set($key, "Crate"));
        if ($expected) {
            $this->assertSame("Crate", $entity->get($key));
        }
    }

    /**
     * @return array<string,array{string,bool}>
     */
    public static function providerSet(): array {
        return [
            "a column"     => [ "name", true ],
            "one it lacks" => [ "notAColumn", false ],
            "nothing"      => [ "", false ],
        ];
    }

    public function testSettingAValueDoesNotMakeTheEntityExist(): void {
        // Only the row the Entity was built from says whether it is there
        $entity = $this->entity();
        $entity->set("name", "Crate");

        $this->assertTrue($entity->isEmpty());
    }



    public function testThePropertiesAreListedWithTheEmptyFlag(): void {
        $expected = [
            "name", "amount", "weight", "isOpen", "kind",
            "sentTime", "extra", "label", "tags", "isEmpty",
        ];

        $this->assertSame($expected, $this->entity()->getProperties());
    }

    public function testTheEntityIsHandedBackAsADictionary(): void {
        $result = $this->entity([ "name" => "Crate", "amount" => 12 ])->toDictionary();

        $this->assertSame("Crate", $result->getString("name"));
        $this->assertSame(12, $result->getInt("amount"));
        $this->assertFalse($result->getBool("isEmpty"));
    }

    public function testTheEntityIsHandedBackAsAnArray(): void {
        $result = $this->entity([ "name" => "Crate" ])->toArray();

        $this->assertSame("Crate", $result["name"]);
        $this->assertSame(0, $result["amount"]);
        $this->assertFalse($result["isEmpty"]);
    }

    public function testTheExtraDataIsAddedToTheArray(): void {
        $result = $this->entity([ "name" => "Crate" ])->toArray([ "extraKey" => "extraValue" ]);

        $this->assertSame("extraValue", $result["extraKey"]);
        $this->assertSame("Crate", $result["name"]);
    }

    public function testTheExtraDataDoesNotOverrideAProperty(): void {
        // The properties are added first, and the sum keeps the left side
        $result = $this->entity([ "name" => "Crate" ])->toArray([ "name" => "Box" ]);

        $this->assertSame("Crate", $result["name"]);
    }

    public function testTheEntityIsSerializedAsItsArray(): void {
        $entity = $this->entity([ "name" => "Crate" ]);

        $this->assertSame($entity->toArray(), $entity->jsonSerialize());
    }
}
