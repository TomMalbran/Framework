<?php
namespace Framework\Builder;

use Framework\Discovery\Discovery;
use Framework\Discovery\DataFile;
use Framework\Core\Settings;
use Framework\Core\VariableType;
use Framework\Utils\Strings;

/**
 * The Setting Code
 */
class SettingCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $data = Discovery::loadData(DataFile::Settings);
        if (empty($data)) {
            return [];
        }

        [ $variables, $hasJSON ] = self::getVariables($data);
        return [
            "sections"  => self::getSections($data),
            "variables" => $variables,
            "hasJSON"   => $hasJSON,
        ];
    }

    /**
     * Returns the Settings Sections for the generator
     * @param array{} $data
     * @return mixed[]
     */
    private static function getSections(array $data): array {
        $result  = [];

        foreach (array_keys($data) as $section) {
            if (!Strings::isEqual($section, Settings::General)) {
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
     * @param array{} $data
     * @return mixed[]
     */
    private static function getVariables(array $data): array {
        $result  = [];
        $isFirst = true;
        $hasJSON = false;

        foreach ($data as $section => $variables) {
            foreach ($variables as $variable => $value) {
                $isGeneral    = Strings::isEqual($section, Settings::General);
                $prefix       = !$isGeneral ? Strings::upperCaseFirst($section) : "";
                $title        = Strings::camelCaseToPascalCase($variable);
                $variableType = VariableType::get($value);

                $result[] = [
                    "isFirst"   => $isFirst,
                    "section"   => $section,
                    "variable"  => $variable,
                    "prefix"    => $prefix,
                    "title"     => !empty($prefix) ? "$prefix $title" : $title,
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
        }
        return [ $result, $hasJSON ];
    }
}
