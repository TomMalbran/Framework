<?php
namespace Tests\Core;

use Framework\Core\SettingConfig;
use Framework\Core\VariableType;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SettingConfigTest extends TestCase {
    use TestHelpers;

    protected function setUp(): void {
        $this->setPrivateStaticProperty(SettingConfig::class, "settings", []);
    }

    protected function tearDown(): void {
        $this->setPrivateStaticProperty(SettingConfig::class, "settings", []);
    }


    public function testRegisterAndGetSettings(): void {
        SettingConfig::register("siteName", SettingConfig::General, VariableType::String, "Test");

        $this->assertSame([
            [
                "variable"     => "siteName",
                "section"      => SettingConfig::General,
                "variableType" => VariableType::String,
                "value"        => "Test",
            ],
        ], SettingConfig::getSettings());
    }


    public function testCollectSettingsWhenEmpty(): void {
        $this->assertSame([], SettingConfig::collectSettings());
    }


    public function testCollectSettingsSectionsSkipGeneral(): void {
        SettingConfig::register("siteName", SettingConfig::General, VariableType::String);
        SettingConfig::register("apiKey", "payments", VariableType::String);

        $result = SettingConfig::collectSettings();

        // The General section is not emitted, only the custom ones
        $this->assertSame([
            [ "section" => "payments", "name" => "Payments" ],
        ], $result["sections"]);
        $this->assertSame(2, $result["total"]);
    }


    #[DataProvider("providerCollectSettingsVariable")]
    public function testCollectSettingsVariable(
        string $variable,
        string $section,
        VariableType $type,
        array $expected,
    ): void {
        SettingConfig::register($variable, $section, $type);
        $result = SettingConfig::collectSettings()["variables"][0];

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key], "key: $key");
        }
    }

    public static function providerCollectSettingsVariable(): array {
        return [
            "general string"    => [
                "siteName",
                SettingConfig::General,
                VariableType::String,
                [
                    "isFirst"   => true,
                    "prefix"    => "",
                    "title"     => "SiteName",
                    "name"      => "SiteName",
                    "type"      => "string",
                    "getter"    => "get",
                    "isString"  => true,
                    "isBoolean" => false,
                ],
            ],
            "sectioned boolean" => [
                "isEnabled",
                "payments",
                VariableType::Boolean,
                [
                    "prefix"    => "Payments",
                    "title"     => "Payments IsEnabled",
                    "type"      => "bool",
                    "getter"    => "is",
                    "isBoolean" => true,
                    "isString"  => false,
                ],
            ],
            "integer"           => [
                "maxItems",
                SettingConfig::General,
                VariableType::Integer,
                [ "type" => "int", "getter" => "get", "isInteger" => true ],
            ],
            "float"             => [
                "taxRate",
                SettingConfig::General,
                VariableType::Float,
                [ "type" => "float", "isFloat" => true ],
            ],
            "array"             => [
                "options",
                SettingConfig::General,
                VariableType::Array,
                [ "type" => "array", "isArray" => true ],
            ],
        ];
    }


    #[DataProvider("providerCollectSettingsHasJSON")]
    public function testCollectSettingsHasJSON(array $types, bool $expected): void {
        foreach ($types as $index => $type) {
            SettingConfig::register("var$index", SettingConfig::General, $type);
        }

        $this->assertSame($expected, SettingConfig::collectSettings()["hasJSON"]);
    }

    public static function providerCollectSettingsHasJSON(): array {
        return [
            "no array"   => [ [ VariableType::String, VariableType::Integer ], false ],
            "with array" => [ [ VariableType::String, VariableType::Array ], true ],
            "only array" => [ [ VariableType::Array ], true ],
        ];
    }


    public function testCollectSettingsMarksOnlyFirst(): void {
        SettingConfig::register("first", SettingConfig::General, VariableType::String);
        SettingConfig::register("second", SettingConfig::General, VariableType::String);

        $variables = SettingConfig::collectSettings()["variables"];

        $this->assertTrue($variables[0]["isFirst"]);
        $this->assertFalse($variables[1]["isFirst"]);
    }


    public function testDestroyCode(): void {
        $this->assertSame(1, SettingConfig::destroyCode());
    }
}
