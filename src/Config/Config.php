<?php
namespace Framework\Config;

use Framework\Framework;
use Framework\File\File;
use Framework\Utils\Strings;
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
            
            $path = Framework::getPath(Framework::ServerDir);
            Dotenv::create($path)->load();
            if (Utils::isStageHost()) {
                if (File::exists($path, ".env.stage")) {
                    Dotenv::create($path, ".env.stage")->overload();
                }
            } elseif (!Utils::isLocalHost()) {
                if (File::exists($path, ".env.production")) {
                    Dotenv::create($path, ".env.production")->overload();
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
        $upperkey = Strings::camelCaseToUpperCase($property);
        if (isset($_ENV[$upperkey])) {
            return $_ENV[$upperkey];
        }

        // Try to get all the properties that start with the value as a prefix
        $result = new stdClass();
        foreach ($_ENV as $envkey => $value) {
            $parts  = Strings::split($envkey, "_");
            $prefix = Strings::toLowerCase($parts[0]);
            if ($prefix == $property) {
                $suffix = Strings::replace($envkey, "{$parts[0]}_", "");
                $key    = Strings::upperCaseToCamelCase($suffix);
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
     * Returns the Url adding the url parts at the end
     * @param string ...$pathParts
     * @return string
     */
    public static function getUrl(...$urlParts) {
        $url  = self::get("url");
        $path = File::getpath(...$urlParts);
        return $url . $path;
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
        $parts = Strings::split($version, "-");
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
