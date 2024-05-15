<?php
namespace Framework\System;

use Framework\Framework;
use Framework\System\VariableType;
use Framework\File\File;
use Framework\Utils\Server;
use Framework\Utils\Strings;

/**
 * The Config Code
 */
class ConfigCode {

    private static bool  $loaded = false;

    /** @var array{}[] */
    private static array $data   = [];

    /** @var string[] */
    private static array $defaults = [
        "DB", "AUTH", "EMAIL", "SMTP", "MAILJET", "MANDRILL", "SEND_GRID",
        "MAILCHIMP", "ONESIGNAL", "OPEN_AI"
    ];



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
            if (empty(trim($line)) || Strings::startsWith($line, "#")) {
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
            } elseif (Strings::contains($value, ".")) {
                $value = (float)$value;
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
    private static function get(string $property, mixed $default = null): mixed {
        self::load();

        // Check if there is a property with the given value
        $upperKey = Strings::camelCaseToUpperCase($property);
        if (isset(self::$data[$upperKey])) {
            return self::$data[$upperKey];
        }

        // Try to get all the properties that start with the value as a prefix
        $found  = false;
        $result = [];
        foreach (self::$data as $envKey => $value) {
            $parts  = Strings::split($envKey, "_");
            $prefix = Strings::toLowerCase($parts[0]);
            if ($prefix == $property) {
                $suffix = Strings::replace($envKey, "{$parts[0]}_", "");
                $key    = Strings::upperCaseToCamelCase($suffix);
                $found  = true;
                $result[$key] = $value;
            }
        }
        if ($found) {
            return $result;
        }

        // We got nothing
        return $default;
    }

    /**
     * Returns a Config Property as a String
     * @param string $property
     * @param string $default  Optional.
     * @return string
     */
    public static function getString(string $property, string $default = ""): string {
        $value = self::get($property);
        return !empty($value) ? (string)$value : $default;
    }

    /**
     * Returns a Config Property as a Boolean
     * @param string $property
     * @return boolean
     */
    public static function getBoolean(string $property): bool {
        $value = self::get($property);
        return !empty($value);
    }

    /**
     * Returns a Config Property as an Int
     * @param string  $property
     * @param integer $default  Optional.
     * @return integer
     */
    public static function getInt(string $property, int $default = 0): int {
        $value = self::get($property);
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
        $value = self::get($property);
        if (!empty($value) && is_numeric($value)) {
            return (float)$value;
        }
        return $default;
    }

    /**
     * Returns a Config Property as an Object
     * @param string $property
     * @return object
     */
    public static function getObject(string $property): object {
        $value = self::get($property);
        return !empty($value) ? (object)$value : (object)[];
    }

    /**
     * Returns a Config Property as an Array
     * @param string $property
     * @return mixed[]
     */
    public static function getArray(string $property): array {
        $value = self::get($property);
        if (!empty($value)) {
            return Strings::split($value, ",");
        }
        return [];
    }

    /**
     * Returns the Url using the given key and adding the url parts at the end
     * @param string $urlKey
     * @param string ...$urlParts
     * @return string
     */
    public static function getUrl(string $urlKey, string ...$urlParts): string {
        $url  = self::get($urlKey, self::get("url"));
        $path = File::getPath(...$urlParts);
        $path = File::removeFirstSlash($path);
        return $url . $path;
    }



    /**
     * Returns the Code variables
     * @return array{}
     */
    public static function getCode(): array {
        self::load();
        if (empty(self::$data)) {
            return [];
        }

        [ $properties, $urls ] = self::getProperties();
        return [
            "urls"       => $urls,
            "properties" => $properties,
        ];
    }

    /**
     * Returns the Config Properties for the generator
     * @return mixed[]
     */
    private static function getProperties(): array {
        $properties = [];
        $urls       = [];

        foreach (self::$data as $envKey => $value) {
            if (Strings::startsWith($envKey, ...self::$defaults)) {
                continue;
            }

            $property = Strings::upperCaseToCamelCase($envKey);
            $title    = Strings::upperCaseToTitle($envKey);
            $name     = Strings::upperCaseFirst($property);

            if (Strings::endsWith($envKey, "URL")) {
                $urls[] = [
                    "property" => $property,
                    "name"     => $name,
                    "title"    => $title,
                ];
                continue;
            }

            $type         = VariableType::get($value);
            $properties[] = [
                "property"  => $property,
                "name"      => $name,
                "title"     => $title,
                "type"      => VariableType::getType($type),
                "docType"   => VariableType::getDocType($type),
                "getter"    => $type === VariableType::Boolean ? "is" : "get",
                "isString"  => $type === VariableType::String,
                "isBoolean" => $type === VariableType::Boolean,
                "isInteger" => $type === VariableType::Integer,
                "isFloat"   => $type === VariableType::Float,
                "isArray"   => $type === VariableType::Array,
            ];
        }
        return [ $properties, $urls ];
    }
}
