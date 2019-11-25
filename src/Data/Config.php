<?php
namespace Framework\Data;

use Framework\Framework;
use Framework\Utils\Utils;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use stdClass;

/**
 * The Config Data
 */
class Config {
    
    private static $loaded = false;
    
    
    /**
     * Loads the Config Data
     * @return void
     */
    public static function load() {
        if (!self::$loaded) {
            self::$loaded = true;
            
            $path = Framework::getPath(Framework::ServerDir);
            Dotenv::create($path)->load();
            if (Utils::isStageHost()) {
                try {
                    Dotenv::create($path, ".env.stage")->overload();
                } catch (InvalidPathException $e) {
                }
            } elseif (!Utils::isLocalHost()) {
                try {
                    Dotenv::create($path, ".env.production")->overload();
                } catch (InvalidPathException $e) {
                }
            }
        }
    }



    /**
     * Returns a Config Property or null
     * @param string $property
     * @return mixed
     */
    public static function get($property) {
        self::load();

        // Check if there is a property with the given value
        $upperkey = Utils::camelcaseToUppercase($property);
        if (isset($_ENV[$upperkey])) {
            return $_ENV[$upperkey];
        }

        // Try to get all the properties that start with the value as a prefix
        $result = new stdClass();
        foreach ($_ENV as $envkey => $value) {
            $parts  = explode("_", $envkey);
            $prefix = strtolower($parts[0]);
            if ($prefix == $property) {
                $suffix = str_replace("{$parts[0]}_", "", $envkey);
                $key    = Utils::uppercaseToCamelcase($suffix);
                $result->{$key} = $value;
            }
        }
        if (!empty($result)) {
            return $result;
        }

        // We got nothing
        return null;
    }

    /**
     * Returns the Version split into the diferent parts
     * @return object
     */
    public static function getVersion() {
        $version = self::get("version");
        if (empty($version)) {
            return (object)[
                "version" => "",
                "build"   => "",
                "full"    => "",
            ];
        }
        $parts = explode("-", $version);
        return (object)[
            "version" => $parts[0],
            "build"   => $parts[1],
            "full"    => $version,
        ];
    }

    /**
     * Returns true if a Property exists
     * @param string $property
     * @return boolean
     */
    public static function has($property) {
        $value = self::get($property);
        return isset($value);
    }
}
