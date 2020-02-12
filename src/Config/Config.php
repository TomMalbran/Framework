<?php
namespace Framework\Config;

use Framework\Framework;
use Framework\File\File;
use Framework\Utils\Server;
use Framework\Utils\Strings;

use Dotenv\Dotenv;
use stdClass;

/**
 * The Config Data
 */
class Config {
    
    private static $loaded = false;
    private static $data   = null;
    
    
    /**
     * Loads the Config Data
     * @return void
     */
    public static function load(): void {
        if (!self::$loaded) {
            $path    = Framework::getPath();
            $data    = Dotenv::createImmutable($path)->load();
            $replace = [];

            if (Server::isDevHost()) {
                if (File::exists($path, ".env.dev")) {
                    $replace = Dotenv::createMutable($path, ".env.dev")->load();
                }
            } elseif (Server::isStageHost()) {
                if (File::exists($path, ".env.stage")) {
                    $replace = Dotenv::createMutable($path, ".env.stage")->load();
                }
            } elseif (!Server::isLocalHost()) {
                if (File::exists($path, ".env.production")) {
                    $replace = Dotenv::createMutable($path, ".env.production")->load();
                }
            }

            self::$loaded = true;
            self::$data   = array_merge($data, $replace);

            foreach (self::$data as $key => $value) {
                if ($value === "true") {
                    self::$data[$key] = true;
                } elseif ($value === "false") {
                    self::$data[$key] = false;
                }
            }
        }
    }



    /**
     * Returns a Config Property or null
     * @param string $property
     * @return mixed
     */
    public static function get(string $property) {
        self::load();

        // Check if there is a property with the given value
        $upperkey = Strings::camelCaseToUpperCase($property);
        if (isset(self::$data[$upperkey])) {
            return self::$data[$upperkey];
        }

        // Try to get all the properties that start with the value as a prefix
        $found  = false;
        $result = new stdClass();
        foreach (self::$data as $envkey => $value) {
            $parts  = Strings::split($envkey, "_");
            $prefix = Strings::toLowerCase($parts[0]);
            if ($prefix == $property) {
                $suffix = Strings::replace($envkey, "{$parts[0]}_", "");
                $key    = Strings::upperCaseToCamelCase($suffix);
                $found  = true;
                $result->{$key} = $value;
            }
        }
        if ($found) {
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
    public static function getUrl(string ...$urlParts): string {
        $url  = self::get("url");
        $path = File::getpath(...$urlParts);
        return $url . $path;
    }

    /**
     * Returns the Version split into the diferent parts
     * @return object
     */
    public static function getVersion(): object {
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
    public static function has(string $property): bool {
        $value = self::get($property);
        return isset($value);
    }
}
