<?php
namespace Framework\Config;

use Framework\Framework;
use Framework\File\File;
use Framework\Utils\Server;
use Framework\Utils\Strings;

use stdClass;

/**
 * The Config Data
 */
class Config {

    private static bool  $loaded = false;

    /** @var array{}[] */
    private static array $data   = [];


    /**
     * Loads the Config Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }
        $path    = Framework::getPath();
        $data    = self::loadENV($path, ".env");
        $replace = [];

        if (Server::isDevHost()) {
            if (File::exists($path, ".env.dev")) {
                $replace = self::loadENV($path, ".env.dev");
            }
        } elseif (Server::isStageHost()) {
            if (File::exists($path, ".env.stage")) {
                $replace = self::loadENV($path, ".env.stage");
            }
        } elseif (!Server::isLocalHost()) {
            if (File::exists($path, ".env.production")) {
                $replace = self::loadENV($path, ".env.production");
            }
        }

        self::$loaded = true;
        self::$data   = array_merge($data, $replace);
        return true;
    }

    /**
     * Parses the Contents of the env files
     * @param string $path
     * @param string $fileName
     * @return array{}
     */
    private static function loadENV(string $path, string $fileName): array {
        $contents = File::read($path, $fileName);
        $lines    = Strings::split($contents, "\n");
        $result   = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $parts = Strings::split($line, " = ");
            if (count($parts) != 2) {
                continue;
            }

            $key   = trim($parts[0]);
            $value = trim($parts[1]);

            if ($value === "true") {
                $value = true;
            } elseif ($value === "false") {
                $value = false;
            } elseif (Strings::startsWith($value, "\"")) {
                $value = Strings::replace($value, "\"", "");
            } else {
                $value = (int)$value;
            }
            $result[$key] = $value;
        }
        return $result;
    }



    /**
     * Returns a Config Property or null
     * @param string     $property
     * @param mixed|null $default  Optional.
     * @return mixed
     */
    public static function get(string $property, mixed $default = null): mixed {
        self::load();

        // Check if there is a property with the given value
        $upperKey = Strings::camelCaseToUpperCase($property);
        if (isset(self::$data[$upperKey])) {
            return self::$data[$upperKey];
        }

        // Try to get all the properties that start with the value as a prefix
        $found  = false;
        $result = new stdClass();
        foreach (self::$data as $envKey => $value) {
            $parts  = Strings::split($envKey, "_");
            $prefix = Strings::toLowerCase($parts[0]);
            if ($prefix == $property) {
                $suffix = Strings::replace($envKey, "{$parts[0]}_", "");
                $key    = Strings::upperCaseToCamelCase($suffix);
                $found  = true;
                $result->{$key} = $value;
            }
        }
        if ($found) {
            return $result;
        }

        // We got nothing
        return $default;
    }

    /**
     * Returns a Config Property as an Int
     * @param string  $property
     * @param integer $default  Optional.
     * @return integer
     */
    public static function getInt(string $property, int $default = 0): int {
        $value = Config::get($property);
        if (!empty($value) && is_numeric($value)) {
            return (int)$value;
        }
        return $default;
    }

    /**
     * Returns a Config Property as a Float
     * @param string $property
     * @param float  $default  Optional.
     * @return float
     */
    public static function getFloat(string $property, float $default = 0): float {
        $value = Config::get($property);
        if (!empty($value) && is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }

    /**
     * Returns a Config Property as an Array
     * @param string $property
     * @return mixed[]
     */
    public static function getArray(string $property): array {
        $value = Config::get($property);
        if (!empty($value)) {
            return Strings::split($value, ",");
        }
        return [];
    }

    /**
     * Returns the Url adding the url parts at the end
     * @param string ...$urlParts
     * @return string
     */
    public static function getUrl(string ...$urlParts): string {
        return self::getUrlPath("url", ...$urlParts);
    }

    /**
     * Returns the File Url adding the url parts at the end
     * @param string ...$urlParts
     * @return string
     */
    public static function getFileUrl(string ...$urlParts): string {
        return self::getUrlPath("fileUrl", ...$urlParts);
    }

    /**
     * Returns the Url using the given key and adding the url parts at the end
     * @param string $urlKey
     * @param string ...$urlParts
     * @return string
     */
    public static function getUrlPath(string $urlKey, string ...$urlParts): string {
        $url  = self::get($urlKey, self::get("url"));
        $path = File::getPath(...$urlParts);
        $path = File::removeFirstSlash($path);
        return $url . $path;
    }

    /**
     * Returns the Version split into the different parts
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
