<?php
namespace {{namespace}};

use Framework\IO\Select;
use Framework\IO\Value\StringValue;
use Framework\Utils\Arrays;
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
     * @return array<string,string>
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
     * @param StringValue|string $value
     * @return bool
     */
    public static function isValid(StringValue|string $value): bool {
        $value = Strings::toString($value);
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
        if ($isoCode !== "root" && isset($languages[$isoCode])) {
            return $isoCode;
        }
        return self::getRootCode();
    }

    /**
     * Creates a Select of Languages
     * @return list<Select>
     */
    public static function getSelect(): array {
        return Select::createFromArray(self::getAll());
    }
}
