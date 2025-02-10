<?php
namespace {{namespace}};

use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The Languages
 */
class Language {

    /**
     * Returns the Language Root Code
     * @return string
     */
    public static function getRootCode(): string {
        return "{{rootCode}}";
    }

    /**
     * Returns all the Languages
     * @return array{}
     */
    public static function getAll(): array {
        return [
        {{#languages}}
            "{{code}}" => "{{name}}",
        {{/languages}}
        ];
    }



    /**
     * Returns true if the given Language Value is valid for the given Group
     * @param string $value
     * @return boolean
     */
    public static function isValid(string $value): bool {
        return Arrays::containsKey(self::getAll(), $value);
    }

    /**
     * Returns a valid Language Code
     * @param string $langName
     * @return string
     */
    public static function getCode(string $langName): string {
        $languages = self::getAll();
        $isoCode   = Strings::toLowerCase($langName);
        if ($isoCode !== "root" && !empty($languages[$isoCode])) {
            return $isoCode;
        }
        return self::getRootCode();
    }

    /**
     * Creates a Select of Languages
     * @return Select[]
     */
    public static function getSelect(): array {
        return Select::createFromMap(self::getAll());
    }
}
