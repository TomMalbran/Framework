<?php
namespace Framework\Core;

use Framework\Discovery\Discovery;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Server;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * The Configs
 */
class Configs {

    private static bool   $loaded      = false;
    private static string $environment = "local";

    /** @var array<string,mixed> */
    private static array $data         = [];

    /** @var string[] */
    private static array $environments = [];



    /**
     * Loads the Config Data
     * @return boolean
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        $framePath   = Discovery::getFramePath();
        $frameData   = self::loadENV($framePath, ".env.example");

        $appPath     = Discovery::getAppPath();
        $appData     = self::loadENV($appPath, ".env");

        $currentUrl  = Server::getUrl();
        $currentHost = Utils::getHost($currentUrl);
        $replace     = [];

        // Read using the getenv function
        $fileName = getenv("ENV_FILENAME");
        if ($fileName !== false) {
            $replace = self::loadENV($appPath, $fileName);

        // Read all the .env files in the App Path
        } else {
            $files = File::getFilesInDir($appPath);
            foreach ($files as $file) {
                if (!Strings::startsWith($file, ".env.")) {
                    continue;
                }
                $environment = Strings::replace($file, ".env.", "");
                if (Arrays::contains(self::$environments, $environment)) {
                    continue;
                }

                $values = self::loadENV($appPath, $file);
                foreach ($values as $key => $value) {
                    if (Strings::endsWith($key, "URL")) {
                        $host = Strings::toString($value);
                        if (Utils::getHost($host) === $currentHost) {
                            self::$environment = $environment;
                            $replace = $values;
                            break;
                        }
                    }
                }
                self::$environments[] = $environment;
            }
        }

        self::$loaded = true;
        self::$data   = Arrays::merge($frameData, $appData, $replace);
        return true;
    }

    /**
     * Parses the Contents of the env files
     * @param string $path
     * @param string $fileName
     * @return array<string,mixed>
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
            if (count($parts) !== 2) {
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
            } elseif (Strings::startsWith($value, "[")) {
                $value = Strings::substringBetween($value, "[", "]");
                $value = Strings::replace($value, [ "\"", " " ], "");
                $value = Strings::split($value, ",");
            } else {
                $value = (int)$value;
            }

            $result[$key] = $value;
        }
        return $result;
    }



    /**
     * Returns the Config Data
     * @return array<string,mixed>
     */
    public static function getData(): array {
        self::load();
        return self::$data;
    }

    /**
     * Returns the Config Environments
     * @return string[]
     */
    public static function getEnvironments(): array {
        self::load();
        return self::$environments;
    }

    /**
     * Returns the Config Environment
     * @return string
     */
    public static function getEnvironment(): string {
        self::load();
        return self::$environment;
    }

    /**
     * Returns a Config Property or null
     * @param string $property
     * @return mixed
     */
    private static function get(string $property): mixed {
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
            if ($prefix === $property) {
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
        return null;
    }

    /**
     * Returns a Config Property as a String
     * @param string $property
     * @param string $default  Optional.
     * @return string
     */
    public static function getString(string $property, string $default = ""): string {
        $value = self::get($property);
        return $value !== null ? Strings::toString($value) : $default;
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
        if ($value !== null) {
            return Numbers::toInt($value);
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
        if ($value !== null) {
            return Numbers::toFloat($value);
        }
        return $default;
    }

    /**
     * Returns a Config Property as a List
     * @param string $property
     * @return string[]
     */
    public static function getList(string $property): array {
        $value = self::get($property);
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            return Arrays::toStrings($value);
        }
        $value = Strings::toString($value);
        return Strings::split($value, ",");
    }
}
