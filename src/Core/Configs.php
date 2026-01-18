<?php
namespace Framework\Core;

use Framework\Application;
use Framework\Discovery\DiscoveryBuilder;
use Framework\Discovery\Package;
use Framework\Discovery\Priority;
use Framework\Builder\Builder;
use Framework\Core\VariableType;
use Framework\File\File;
use Framework\Utils\Arrays;
use Framework\Utils\Numbers;
use Framework\Utils\Server;
use Framework\Utils\Strings;
use Framework\Utils\Utils;

/**
 * The Configs
 */
#[Priority(Priority::Highest)]
class Configs implements DiscoveryBuilder {

    private static bool   $loaded      = false;
    private static string $environment = "local";
    private static string $fileName    = "";

    /** @var array<string,mixed> */
    private static array $data         = [];

    /** @var string[] */
    private static array $environments = [];



    /**
     * Sets the Config File Name
     * @param string $fileName
     * @return bool
     */
    public static function setFileName(string $fileName): bool {
        self::$fileName = $fileName;
        return true;
    }

    /**
     * Loads the Config Data
     * @return bool
     */
    public static function load(): bool {
        if (self::$loaded) {
            return false;
        }

        $framePath   = Package::getBasePath();
        $frameData   = self::loadENV($framePath, ".env.example");

        $appPath     = Application::getBasePath();
        $appData     = self::loadENV($appPath, ".env");

        $currentUrl  = Server::getUrl();
        $currentHost = Utils::getHost($currentUrl);
        $replace     = [];

        // Read using the getenv function or the saved file name
        $fileName = getenv("ENV_FILENAME");
        if (self::$fileName !== "") {
            $fileName = self::$fileName;
        }
        if ($fileName !== false) {
            $replace     = self::loadENV($appPath, $fileName);
            $environment = Strings::replace($fileName, ".env.", "");
            if (Arrays::contains(self::$environments, $environment)) {
                self::$environment = $environment;
            }

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
            if (trim($line) === "" || Strings::startsWith($line, "#")) {
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
        $upperKey = Strings::camelCaseToUpperCase($property);
        return self::$data[$upperKey] ?? null;
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
     * @return bool
     */
    public static function getBoolean(string $property): bool {
        $value = self::get($property);
        return !Arrays::isEmpty($value);
    }

    /**
     * Returns a Config Property as an Int
     * @param string $property
     * @param int    $default  Optional.
     * @return int
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



    /**
     * Generates the code
     * @return int
     */
    #[\Override]
    public static function generateCode(): int {
        $data = self::getData();
        if (Arrays::isEmpty($data)) {
            return Builder::generateCode("Config");
        }

        $environments = [];
        foreach (self::getEnvironments() as $environment) {
            $environments[] = [
                "name"        => Strings::upperCaseFirst($environment),
                "environment" => $environment,
            ];
        }

        // Builds the code
        [ $urls, $properties ] = self::getProperties($data);
        return Builder::generateCode("Config", [
            "environments" => $environments,
            "urls"         => $urls,
            "properties"   => $properties,
            "total"        => count($properties),
        ]);
    }

    /**
     * Destroys the Code
     * @return int
     */
    #[\Override]
    public static function destroyCode(): int {
        return 1;
    }

    /**
     * Returns the Config Properties for the generator
     * @param array<string,mixed> $data
     * @return array{array<string,mixed>[],array<string,mixed>[]}
     */
    private static function getProperties(array $data): array {
        $urls       = [];
        $properties = [];

        foreach ($data as $envKey => $value) {
            $property = Strings::upperCaseToCamelCase($envKey);
            $title    = Strings::upperCaseToPascalCase($envKey);
            $name     = Strings::upperCaseFirst($property);

            if (Strings::endsWith($envKey, "URL")) {
                $urls[] = [
                    "property" => $property,
                    "name"     => $name,
                    "title"    => $title,
                ];
                continue;
            }

            $variableType = VariableType::get($value, true);
            $properties[] = [
                "property"  => $property,
                "name"      => $name,
                "title"     => $title,
                "type"      => VariableType::getType($variableType),
                "docType"   => VariableType::getDocType($variableType),
                "getter"    => $variableType === VariableType::Boolean ? "is" : "get",
                "isString"  => $variableType === VariableType::String,
                "isBoolean" => $variableType === VariableType::Boolean,
                "isInteger" => $variableType === VariableType::Integer,
                "isFloat"   => $variableType === VariableType::Float,
                "isList"    => $variableType === VariableType::List,
            ];
        }

        return [ $urls, $properties ];
    }
}
