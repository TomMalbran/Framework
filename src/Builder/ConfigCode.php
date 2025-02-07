<?php
namespace Framework\Builder;

use Framework\Core\Configs;
use Framework\Core\VariableType;
use Framework\Utils\Strings;

/**
 * The Config Code
 */
class ConfigCode {

    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        $data = Configs::getData();
        if (empty($data)) {
            return [];
        }

        [ $urls, $properties ] = self::getProperties($data);
        return [
            "environments" => self::getEnvironments(),
            "urls"         => $urls,
            "properties"   => $properties,
        ];
    }

    /**
     * Returns the Config getEnvironments for the generator
     * @return mixed[]
     */
    private static function getEnvironments(): array {
        $result = [];
        foreach (Configs::getEnvironments() as $environment) {
            $result[] = [
                "name"        => Strings::upperCaseFirst($environment),
                "environment" => $environment,
            ];
        }
        return $result;
    }

    /**
     * Returns the Config Properties for the generator
     * @param array<string,mixed> $data
     * @return mixed[]
     */
    private static function getProperties(array $data): array {
        $urls       = [];
        $properties = [];

        foreach ($data as $envKey => $value) {
            $property = Strings::upperCaseToCamelCase($envKey);
            $title    = Strings::upperCaseToPascalCase($envKey);
            $name     = Strings::upperCaseFirst($property);

            if (Strings::endsWith($envKey, "URL")) {
                $urls[] = [
                    "property" => $property,
                    "name"     => $name,
                    "title"    => $title,
                ];
                continue;
            }

            $type         = VariableType::get($value);
            $properties[] = [
                "property"  => $property,
                "name"      => $name,
                "title"     => $title,
                "type"      => VariableType::getType($type),
                "docType"   => VariableType::getDocType($type),
                "getter"    => $type === VariableType::Boolean ? "is" : "get",
                "isString"  => $type === VariableType::String,
                "isBoolean" => $type === VariableType::Boolean,
                "isInteger" => $type === VariableType::Integer,
                "isFloat"   => $type === VariableType::Float,
                "isArray"   => $type === VariableType::Array,
            ];
        }

        return [ $urls, $properties ];
    }
}
