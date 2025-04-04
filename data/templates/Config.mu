<?php
namespace {{namespace}};

use Framework\Core\Configs;
use Framework\File\File;

/**
 * The Config
 */
class Config {

    /**
     * Returns true if the Environment is "Local"
     * @return boolean
     */
    public static function isLocal(): bool {
        return Configs::getEnvironment() === "local";
    }
{{#environments}}

    /**
     * Returns true if the Environment is "{{name}}"
     * @return boolean
     */
    public static function is{{name}}(): bool {
        return Configs::getEnvironment() === "{{environment}}";
    }
{{/environments}}


    /**
     * Returns the url for the given key and adding the url parts at the end
     * @param string $urlKey
     * @param string|integer ...$urlParts
     * @return string
     */
    public static function getUrlWithKey(string $urlKey, string|int ...$urlParts): string {
        $url  = Configs::getString($urlKey, Configs::getString("url"));
        $path = File::parsePath(...$urlParts);
        $path = File::removeFirstSlash($path);
        return $url . $path;
    }
{{#urls}}

    /**
     * Returns the "{{title}}" using the adding the url parts at the end
     * @param string|integer ...$urlParts
     * @return string
     */
    public static function get{{name}}(string|int ...$urlParts): string {
        return self::getUrlWithKey("{{property}}", ...$urlParts);
    }
{{/urls}}


{{#properties}}

    /**
     * Returns the value of the "{{title}}"
     * @return {{{docType}}}
     */
    public static function {{getter}}{{name}}(): {{type}} {
        {{#isString}}
        return Configs::getString("{{property}}");
        {{/isString}}
        {{#isBoolean}}
        return Configs::getBoolean("{{property}}");
        {{/isBoolean}}
        {{#isInteger}}
        return Configs::getInt("{{property}}");
        {{/isInteger}}
        {{#isFloat}}
        return Configs::getFloat("{{property}}");
        {{/isFloat}}
        {{#isList}}
        return Configs::getList("{{property}}");
        {{/isList}}
    }
{{/properties}}
}
