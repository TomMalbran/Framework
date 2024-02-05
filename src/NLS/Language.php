<?php
namespace Framework\NLS;

use Framework\Framework;
use Framework\NLS\LanguageEntity;
use Framework\Utils\Arrays;
use Framework\Utils\Select;
use Framework\Utils\Strings;

/**
 * The NLS Languages
 */
class Language {

    private static bool $loaded = false;

    private static ?LanguageEntity $root = null;

    /** @var LanguageEntity[] */
    private static array $languages = [];



    /**
     * Loads the Language Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        $data = Framework::loadData(Framework::LanguageData);
        if (empty($data)) {
            $data = Framework::loadJSON(Framework::DataDir, Framework::LanguageData, true);
        }

        foreach ($data as $key => $elem) {
            $entity = new LanguageEntity($elem);
            self::$languages[$key] = $entity;
            if ($entity->isRoot) {
                self::$root = $entity;
            }
        }
        if (empty(self::$root)) {
            self::$root = Arrays::getFirst(self::$languages);
        }

        self::$loaded = true;
        return true;
    }



    /**
     * Returns true if the given Language Value is valid for the given Group
     * @param string $value
     * @return boolean
     */
    public static function isValid(string $value): bool {
        self::load();
        return Arrays::containsKey(self::$languages, $value);
    }

    /**
     * Returns the Language Value from a Language Name
     * @param string $langName
     * @return LanguageEntity|null
     */
    public static function getOne(string $langName): ?LanguageEntity {
        self::load();
        $isoCode = Strings::toLowerCase($langName);
        if (isset(self::$languages[$isoCode])) {
            return self::$languages[$isoCode];
        }
        return null;
    }

    /**
     * Returns a valid Language Code
     * @param string $langName
     * @return string
     */
    public static function getCode(string $langName): string {
        self::load();
        $isoCode = Strings::toLowerCase($langName);
        if ($isoCode != "root" && !empty(self::$languages[$isoCode])) {
            return $isoCode;
        }
        return self::$root->key;
    }

    /**
     * Returns the Language Root Code
     * @return string
     */
    public static function getRootCode(): string {
        self::load();
        return self::$root->key;
    }

    /**
     * Returns all the Languages
     * @return array{}
     */
    public static function getAll(): array {
        self::load();
        return Arrays::createMap(self::$languages, "key", "name");
    }

    /**
     * Creates a Select of Languages
     * @return Select[]
     */
    public static function getSelect(): array {
        self::load();
        return Select::create(self::$languages, "key", "name");
    }



    /**
     * Returns a value depending on the call name
     * @param string $function
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $function, array $arguments) {
        self::load();
        $value = !empty($arguments[0]) ? $arguments[0] : "";

        // Function "isXxx": isSpanish("es") => true, isSpanish("en") => false
        if (Strings::startsWith($function, "is")) {
            $languageName = Strings::stripStart($function, "is");
            $language     = self::getOne($value);
            return !empty($language) && Strings::isEqual($language->name, $languageName);
        }

        // Function "xxx": Spanish() => "es"
        foreach (self::$languages as $isoCode => $language) {
            if (Strings::isEqual($language->name, $function)) {
                return $isoCode;
            }
        }

        // Function "xxx": ES() => {}
        return self::getOne($function);
    }
}
