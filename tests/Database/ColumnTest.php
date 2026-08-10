<?php
namespace Tests\Database;

use Framework\Core\Schema\SettingsColumn;
use Framework\Database\Status\StateColor;
use Framework\Database\Status\Status;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Column trait and the Status, which the build writes into every Model
 */
class ColumnTest extends TestCase {

    /**
     * A Column, and the three names it answers with
     * @param SettingsColumn $column
     * @param string         $name
     * @param string         $key
     * @param string         $base
     * @return void
     */
    #[DataProvider("providerColumn")]
    public function testTheColumnAnswersWithEachOfItsNames(
        SettingsColumn $column,
        string $name,
        string $key,
        string $base,
    ): void {
        $this->assertSame($name, $column->name());
        $this->assertSame($key, $column->key());
        $this->assertSame($base, $column->base());
    }

    /**
     * The name is the column with its table, the base is the column alone,
     * and the key is the property the Entity holds it in
     * @return array<string,array{SettingsColumn,string,string,string}>
     */
    public static function providerColumn(): array {
        return [
            "a column"         => [
                SettingsColumn::Section, "settings.section", "section", "section",
            ],
            "one of two words" => [
                SettingsColumn::VariableType, "settings.variableType", "variableType", "variableType",
            ],
            "the empty one"    => [
                SettingsColumn::None, "", "none", "",
            ],
        ];
    }

    /**
     * What toKeys() is handed, and the keys it hands back
     * @param list<SettingsColumn>|SettingsColumn|null $given
     * @param list<string>                             $expected
     * @return void
     */
    #[DataProvider("providerKeys")]
    public function testTheKeysAreTakenFromOneColumnOrMany(
        array|SettingsColumn|null $given,
        array $expected,
    ): void {
        $this->assertSame($expected, SettingsColumn::toKeys($given));
    }

    /**
     * @return array<string,array{list<SettingsColumn>|SettingsColumn|null,list<string>}>
     */
    public static function providerKeys(): array {
        return [
            "nothing given" => [ null, [] ],
            "one column"    => [ SettingsColumn::Section, [ "section" ] ],
            "no columns"    => [ [], [] ],
            "two columns"   => [
                [ SettingsColumn::Section, SettingsColumn::Variable ],
                [ "section", "variable" ],
            ],
        ];
    }



    public function testTheStatusOffersTheTwoStatesWithTheirColors(): void {
        $this->assertSame([
            "Active"   => StateColor::Green->getColor(),
            "Inactive" => StateColor::Red->getColor(),
        ], Status::getValues());
    }

    public function testTheStatusPutsBothStatesInOneGroup(): void {
        $this->assertSame([
            "General" => [ "Active", "Inactive" ],
        ], Status::getGroups());
    }

    /**
     * A State color, and the name the build writes for it
     * @param StateColor $color
     * @param string     $expected
     * @return void
     */
    #[DataProvider("providerColors")]
    public function testTheColorIsNamedInLowerCase(StateColor $color, string $expected): void {
        $this->assertSame($expected, $color->getColor());
    }

    /**
     * @return array<string,array{StateColor,string}>
     */
    public static function providerColors(): array {
        return [
            "green"   => [ StateColor::Green, "green" ],
            "red"     => [ StateColor::Red, "red" ],
            "nothing" => [ StateColor::None, "none" ],
        ];
    }
}
