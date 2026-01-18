<?php
namespace Framework\Core;

use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Priority;
use Framework\Builder\Builder;
use Framework\Core\VariableType;
use Framework\Utils\Strings;

/**
 * The Setting Config
 */
#[Priority(Priority::Highest)]
class SettingConfig implements DiscoveryBuilder {

    public const Core    = "Core";
    public const General = "General";


    /** @var array{variable:string,section:string,variableType:VariableType,value:mixed}[] */
    private static array $settings = [];


    /**
     * Registers a new Setting
     * @param string       $variable
     * @param string       $section
     * @param VariableType $variableType
     * @param mixed        $value        Optional.
     * @return bool
     */
    public static function register(
        string $variable,
        string $section,
        VariableType $variableType,
        mixed $value = "",
    ): bool {
        self::$settings[] = [
            "variable"     => $variable,
            "section"      => $section,
            "variableType" => $variableType,
            "value"        => $value,
        ];
        return true;
    }

    /**
     * Returns the registered Settings
     * @return array{variable:string,section:string,variableType:VariableType,value:mixed}[]
     */
    public static function getSettings(): array {
        return self::$settings;
    }



    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        if (count(self::$settings) === 0) {
            return Builder::generateCode("Setting");
        }

        [ $variables, $hasJSON ] = self::getVariables();
        return Builder::generateCode("Setting", [
            "sections"  => self::getSections(),
            "variables" => $variables,
            "hasJSON"   => $hasJSON,
            "total"     => count($variables),
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }

    /**
     * Returns the Settings Sections for the generator
     * @return array{section:string,name:string}[]
     */
    private static function getSections(): array {
        $result = [];

        foreach (self::$settings as $setting) {
            $section = $setting["section"];
            if (!Strings::isEqual($section, self::General)) {
                $result[] = [
                    "section" => $section,
                    "name"    => Strings::upperCaseFirst($section),
                ];
            }
        }
        return $result;
    }

    /**
     * Returns the Settings Variables for the generator
     * @return array{array<string,mixed>[],bool}
     */
    private static function getVariables(): array {
        $result  = [];
        $isFirst = true;
        $hasJSON = false;

        foreach (self::$settings as $setting) {
            $section      = $setting["section"];
            $variable     = $setting["variable"];
            $variableType = $setting["variableType"];

            $isGeneral    = Strings::isEqual($section, self::General);
            $prefix       = !$isGeneral ? Strings::upperCaseFirst($section) : "";
            $title        = Strings::camelCaseToPascalCase($variable);

            $result[] = [
                "isFirst"   => $isFirst,
                "section"   => $section,
                "variable"  => $variable,
                "prefix"    => $prefix,
                "title"     => $prefix !== "" ? "$prefix $title" : $title,
                "name"      => Strings::upperCaseFirst($variable),
                "type"      => VariableType::getType($variableType),
                "docType"   => VariableType::getDocType($variableType),
                "getter"    => $variableType === VariableType::Boolean ? "is" : "get",
                "isBoolean" => $variableType === VariableType::Boolean,
                "isInteger" => $variableType === VariableType::Integer,
                "isFloat"   => $variableType === VariableType::Float,
                "isString"  => $variableType === VariableType::String,
                "isArray"   => $variableType === VariableType::Array,
            ];

            $isFirst = false;
            if ($variableType === VariableType::Array) {
                $hasJSON = true;
            }
        }
        return [ $result, $hasJSON ];
    }
}
