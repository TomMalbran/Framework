<?php
namespace Framework\Discovery;

use Framework\File\File;
use Framework\Discovery\Package;
use Framework\Utils\Arrays;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;
use Framework\Utils\JSON;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionNamedType;
use Throwable;

/**
 * The Discovery
 */
class Discovery {

    /**
     * Returns the BasePath
     * @param boolean $forFramework Optional.
     * @param boolean $forBackend   Optional.
     * @return string
     */
    public static function getBasePath(bool $forFramework = false, bool $forBackend = false): string {
        if ($forFramework) {
            return self::getFramePath();
        }
        if ($forBackend) {
            return self::getAppPath();
        }
        return self::getIndexPath();
    }

    /**
     * Returns the path to the Index
     * @param string ...$pathParts
     * @return string
     */
    public static function getIndexPath(string ...$pathParts): string {
        $path = self::getFramePath();
        if (Strings::contains($path, "vendor")) {
            $path = Strings::substringBefore($path, "/vendor");
            $path = Strings::substringBefore($path, "/", false);
        }
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the Framework
     * @param string ...$pathParts
     * @return string
     */
    public static function getFramePath(string ...$pathParts): string {
        $path = File::getDirectory(__FILE__, 3);
        return File::parsePath($path, ...$pathParts);
    }

    /**
     * Returns the path to the App
     * @param string ...$pathParts
     * @return string
     */
    public static function getAppPath(string ...$pathParts): string {
        return self::getIndexPath(Package::getAppBaseDir(), ...$pathParts);
    }

    /**
     * Returns the path to the Source Directory
     * @param string ...$pathParts
     * @return string
     */
    public static function getSourcePath(string ...$pathParts): string {
        return self::getAppPath(Package::getAppSourceDir(), ...$pathParts);
    }

    /**
     * Returns the path to the Strings Directory
     * @return string
     */
    public static function getStringsPath(): string {
        return self::getAppPath(Package::StringsDir);
    }

    /**
     * Returns the path to the Migrations
     * @return string
     */
    public static function getMigrationsPath(): string {
        return self::getAppPath(Package::MigrationsDir);
    }

    /**
     * Returns the Namespace used in the Builder
     * @return string
     */
    public static function getBuildPath(): string {
        return self::getFramePath(Package::getAppSourceDir(), Package::SystemDir);
    }



    /**
     * Returns the Environment
     * @return string
     */
    public static function getEnvironment(): string {
        $basePath = self::getBasePath(false);
        if (Strings::contains($basePath, "public_html")) {
            $environment = Strings::substringAfter($basePath, "domains/");
            $environment = Strings::substringBefore($environment, "/public_html");
            return $environment;
        }
        return "localhost";
    }

    /**
     * Checks if the given File exists in the App Data Directory
     * @param string $fileName
     * @return boolean
     */
    public static function hasDataFile(string $fileName): bool {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getAppPath(Package::DataDir, $file);
        return File::exists($path);
    }

    /**
     * Loads a File from the App or defaults to the Framework
     * @return string
     */
    public static function loadEmailTemplate(): string {
        $path   = self::getAppPath(Package::DataDir, Package::EmailFile);
        $result = "";
        if (File::exists($path)) {
            $result = File::read($path);
        }
        if ($result === "") {
            $path   = self::getFramePath(Package::DataDir, Package::EmailFile);
            $result = File::read($path);
        }
        return $result;
    }

    /**
     * Loads a JSON File
     * @param string $dir
     * @param string $fileName
     * @return array<string|integer,mixed>
     */
    public static function loadJSON(string $dir, string $fileName): array {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getAppPath($dir, $file);
        return JSON::readFile($path);
    }

    /**
     * Loads the Strings File for the given Language
     * @param string $langCode
     * @return array<string,mixed>
     */
    public static function loadStrings(string $langCode): array {
        $result = self::loadJSON(Package::StringsDir, $langCode);
        return Arrays::toStringMixedMap($result);
    }

    /**
     * Loads the Emails File for the given Language
     * @param string $langCode
     * @return array<string,mixed>
     */
    public static function loadEmails(string $langCode): array {
        $result = self::loadJSON(Package::EmailsDir, $langCode);
        return Arrays::toStringMixedMap($result);
    }

    /**
     * Loads the Notifications File for the given Language
     * @param string $langCode
     * @return array<string,mixed>
     */
    public static function loadNotifications(string $langCode): array {
        $result = self::loadJSON(Package::NotificationsDir, $langCode);
        return Arrays::toStringMixedMap($result);
    }

    /**
     * Loads a Custom Data File
     * @param string $fileName
     * @return array<string,Dictionary>
     */
    public static function loadCustomData(string $fileName): array {
        $data   = self::loadJSON(Package::DataDir, $fileName);
        $result = [];
        foreach ($data as $key => $value) {
            $result[Strings::toString($key)] = new Dictionary($value);
        }
        return $result;
    }

    /**
     * Saves a Data File
     * @param string $fileName
     * @param mixed  $contents
     * @return boolean
     */
    public static function saveData(string $fileName, mixed $contents): bool {
        $file = Strings::addSuffix($fileName, ".json");
        $path = self::getAppPath(Package::DataDir, $file);
        return JSON::writeFile($path, $contents);
    }

    /**
     * Logs a JSON File
     * @param string $file
     * @param mixed  $contents
     * @return boolean
     */
    public static function logFile(string $file, mixed $contents): bool {
        $path = self::getAppPath(Package::LogDir);
        File::createDir($path);
        return File::write("$path/$file.json", JSON::encode($contents, true));
    }



    /**
     * Finds the Classes in the given Directory
     * @param string  $dir          Optional.
     * @param boolean $forFramework Optional.
     * @return array<string,string>
     */
    public static function findClasses(
        string $dir = "",
        bool $forFramework = false,
    ): array {
        if ($forFramework) {
            $namespace  = Package::FrameNamespace;
            $sourcePath = self::getFramePath(Package::FrameSourceDir);
        } else {
            $namespace  = Package::getAppNamespace();
            $sourcePath = self::getSourcePath();
        }

        $filePaths  = File::getFilesInDir($sourcePath, true);
        $classPaths = [];
        $result     = [];

        // Find all the Classes
        foreach ($filePaths as $filePath) {
            if (!Strings::endsWith($filePath, ".php")) {
                continue;
            }

            $className = Strings::replace($filePath, [ $sourcePath, ".php" ], "");
            $className = Strings::substringAfter($className, "/", true);
            $className = Strings::replace($className, "/", "\\");
            $className = "{$namespace}{$className}";

            $classPaths[$className] = $filePath;
        }

        // Filter the Classes that have all their dependencies available
        foreach ($classPaths as $className => $filePath) {
            // Skip some ignored directories
            if (Strings::contains($filePath, "/Schema/", "/System/")) {
                continue;
            }

            // Skip using the given Directory
            if ($dir !== "" && !Strings::contains($filePath, "/$dir/")) {
                continue;
            }

            $file        = File::read($filePath);
            $lines       = Strings::split($file, "\n", trim: true, skipEmpty: true);
            $usedClasses = [];
            $isValid     = true;

            foreach ($lines as $line) {
                if (Strings::startsWith($line, "use ")) {
                    $usedClass     = Strings::substringBetween($line, "use ", ";");
                    $usedClassName = Strings::substringAfter($usedClass, "\\");
                    $usedClasses[$usedClassName] = $usedClass;
                    continue;
                }

                // Only Validate the Classes for the current Namespace
                $namespace = Package::FrameNamespace;
                if (!$forFramework) {
                    $namespace = Package::getAppNamespace();
                }

                // Check that the used Class exists
                if (Strings::startsWith($line, "class") && Strings::contains($line, "extends")) {
                    $parentExtends = Strings::substringBetween($line, "extends ", " ");
                    $parentClasses = Strings::split($parentExtends, ",", trim: true);
                    foreach ($parentClasses as $parentClass) {
                        $fullClassName = $usedClasses[$parentClass] ?? "";
                        if (Strings::startsWith($fullClassName, $namespace) && !isset($classPaths[$fullClassName])) {
                            $isValid = false;
                            break 2;
                        }
                    }
                }
            }

            if ($isValid) {
                $classKey = Strings::substringAfter($className, "\\");
                $result["\\$className"] = $classKey;
            }
        }
        return $result;
    }

    /**
     * Returns the Reflection Classes in the given Directory
     * @param string  $dir          Optional.
     * @param boolean $forFramework Optional.
     * @return array<string,ReflectionClass<object>>
     */
    public static function getReflectionClasses(
        string $dir = "",
        bool $forFramework = false,
    ): array {
        $classes = self::findClasses($dir, $forFramework);
        $result  = [];

        foreach ($classes as $className => $classKey) {
            try {
                if (class_exists($className)) {
                    $result[$className] = new ReflectionClass($className);
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return $result;
    }

    /**
     * Returns the Reflection Classes that implement the given interface
     * @param string  $interface
     * @param string  $dir          Optional.
     * @param boolean $forFramework Optional.
     * @return array{}
     */
    public static function getClassesWithInterface(
        string $interface,
        string $dir = "",
        bool $forFramework = false,
    ): array {
        $reflections = self::getReflectionClasses($dir, $forFramework);
        $priorities  = [];
        $instances   = [];
        $result      = [];

        foreach ($reflections as $reflection) {
            if ($reflection->implementsInterface($interface)) {
                $priority = self::getPriority($reflection);
                if (!isset($instances[$priority])) {
                    $priorities[] = $priority;
                }
                $instances[$priority][] = $reflection->newInstance();
            }
        }
        sort($priorities);

        foreach ($priorities as $priority) {
            foreach ($instances[$priority] as $instance) {
                $result[] = $instance;
            }
        }
        return $result;
    }

    /**
     * Returns the Properties of the given Class
     * @param object       $class
     * @param integer|null $filter Optional.
     * @return array<string,string>
     */
    public static function getProperties(object $class, ?int $filter = null): array {
        $result     = [];
        $reflection = new ReflectionClass($class);

        /** @var ReflectionProperty[] */
        $props = $reflection->getProperties($filter ?? ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        foreach ($props as $prop) {
            $type     = $prop->getType();
            $typeName = "mixed";
            if ($type !== null && $type instanceof ReflectionNamedType) {
                $typeName = $type->getName();
            }
            $result[$prop->getName()] = $typeName;
        }
        return $result;
    }

    /**
     * Returns the Properties of the given Class, starting from the Base Class
     * @param ReflectionClass<object> $class
     * @return array<ReflectionProperty>
     */
    public static function getPropertiesBaseFirst(ReflectionClass $class): array {
        $result = [];
        do {
            $properties = [];
            foreach ($class->getProperties() as $property) {
                if ($property->getDeclaringClass()->getName() === $class->getName()) {
                    $properties[] = $property;
                }
            }
            $result = array_merge($properties, $result);
        } while ($class = $class->getParentClass());
        return $result;
    }

    /**
     * Returns the priority from a ReflectionClass or ReflectionMethod.
     * @param ReflectionClass<object>|ReflectionMethod $reflection
     * @return integer
     */
    public static function getPriority(ReflectionClass|ReflectionMethod $reflection): int {
        $attributes = $reflection->getAttributes(Priority::class);
        if (isset($attributes[0])) {
            return $attributes[0]->newInstance()->priority;
        }
        return Priority::Normal;
    }
}
