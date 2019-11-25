<?php
namespace Framework\Data;

use Framework\Framework;
use Framework\Utils\Utils;

use Dotenv\Dotenv;
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
            
            $path = Framework::getPath();
            Dotenv::create($path)->load();
            if (!Utils::isSageHost()) {
                Dotenv::create($path, ".env.stage")->overload();
            } elseif (!Utils::isLocalHost()) {
                Dotenv::create($path, ".env.production")->overload();
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
                $key = Utils::uppercaseToCamelcase($envkey);
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
            return null;
        }
        $parts = explode("-", $version);
        return (object)[
            "version" => $parts[0],
            "build"   => $parts[1],
            "full"    => $verstion,
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
