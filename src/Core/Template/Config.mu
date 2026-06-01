<?php
namespace {{namespace}};

use Framework\Core\Configs;
use Framework\File\Storage;

/**
 * The Config
 */
class Config {

    /**
     * Returns true if the Environment is "Local"
     * @return bool
     */
    public static function isLocal(): bool {
        return Configs::getEnvironment() === "local";
    }
{{#environments}}

    /**
     * Returns true if the Environment is "{{name}}"
     * @return bool
     */
    public static function is{{name}}(): bool {
        return Configs::getEnvironment() === "{{environment}}";
    }
{{/environments}}


    /**
     * Returns the url for the given key and adding the url parts at the end
     * @param string $urlKey
     * @param int|string ...$urlParts
     * @return string
     */
    public static function getUrlWithKey(string $urlKey, int|string ...$urlParts): string {
        $url = Configs::getString($urlKey);
        if ($url === "") {
            $url = Configs::getString("url");
        }

        $path = Storage::parsePath(...$urlParts);
        $path = Storage::removeFirstSlash($path);
        return $url . $path;
    }
{{#urls}}

    /**
     * Returns the "{{name}}" using the adding the url parts at the end
     * @param int|string ...$urlParts
     * @return string
     */
    public static function get{{name}}(int|string ...$urlParts): string {
        return self::getUrlWithKey("{{property}}", ...$urlParts);
    }
{{/urls}}


{{#properties}}

    /**
     * Returns the value of the "{{name}}"
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
