<?php
namespace {{codeSpace}};

use Framework\System\ConfigCode;

/**
 * The Config
 */
class Config {
{{#urls}}

    /**
     * Returns the "{{title}}" using the adding the url parts at the end
     * @param string ...$urlParts
     * @return string
     */
    public static function get{{name}}(string ...$urlParts): string {
        return ConfigCode::getUrl("{{property}}", ...$urlParts);
    }
{{/urls}}


{{#properties}}

    /**
     * Returns the value of the "{{title}}"
     * @return {{docType}}
     */
    public static function {{getter}}{{name}}(): {{type}} {
        {{#isString}}
        return ConfigCode::getString("{{property}}");
        {{/isString}}
        {{#isBoolean}}
        return ConfigCode::getBoolean("{{property}}");
        {{/isBoolean}}
        {{#isInteger}}
        return ConfigCode::getInt("{{property}}");
        {{/isInteger}}
        {{#isFloat}}
        return ConfigCode::getFloat("{{property}}");
        {{/isFloat}}
        {{#isArray}}
        return ConfigCode::getArray("{{property}}");
        {{/isArray}}
    }
{{/properties}}
}
