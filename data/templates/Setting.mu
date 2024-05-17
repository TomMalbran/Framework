<?php
namespace {{codeSpace}};

use Framework\System\SettingCode;{{#hasJSON}}
use Framework\Utils\JSON;{{/hasJSON}}

/**
 * The Setting
 */
class Setting {

    /**
     * Returns all the Settings
     * @return array{}
     */
    public static function getAll(): array {
        return SettingCode::getAll();
    }

    /**
     * Saves all the Settings
     * @param array{} $data
     * @return boolean
     */
    public static function saveAll(array $data): bool {
        return SettingCode::saveAll($data);
    }



{{#sections}}
    /**
     * Returns all the Settings for {{name}}
     * @param boolean $asObject Optional.
     * @return array{}|object
     */
    public static function getAll{{name}}(bool $asObject = false): array|object {
        return SettingCode::getAll("{{section}}", $asObject);
    }

    /**
     * Saves all the Settings for {{name}}
     * @param array{} $data
     * @return boolean
     */
    public static function save{{name}}(array $data): bool {
        return SettingCode::saveSection("{{section}}", $data);
    }

{{/sections}}
{{#variables}}
{{^isFirst}}



{{/isFirst}}
    /**
     * Returns the value of "{{title}}"
     * @return {{docType}}
     */
    public static function {{getter}}{{prefix}}{{name}}(): {{type}} {
        $result = SettingCode::get("{{section}}", "{{variable}}");
        {{#isBoolean}}
        return !empty($result);
        {{/isBoolean}}
        {{#isInteger}}
        return $result !== null ? (int)$result : 0;
        {{/isInteger}}
        {{#isFloat}}
        return $result !== null ? (float)$result : 0;
        {{/isFloat}}
        {{#isString}}
        return $result !== null ? (string)$result : "";
        {{/isString}}
        {{#isArray}}
        return $result !== null ? JSON::decode($result) : [];
        {{/isArray}}
    }

    /**
     * Sets the value of "{{title}}"
     * @param {{docType}} $value
     * @return boolean
     */
    public static function set{{prefix}}{{name}}({{type}} $value): bool {
        {{#isBoolean}}
        $value = !empty($value) ? 1 : 0;
        {{/isBoolean}}
        {{#isArray}}
        $value = JSON::encode($value);
        {{/isArray}}
        return SettingCode::set("{{section}}", "{{variable}}", (string)$value);
    }
{{/variables}}
}
