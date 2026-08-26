<?php
namespace Tests\Tools;

use Framework\Analysis\Attr\NotTested;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

/**
 * The reach of the suite
 *
 * Every public method of a class that has a Test file is called somewhere in
 * it, or says why not. The check is made from outside the tests rather than
 * inside each of them, so a class written later is covered without anyone
 * having to remember to ask for it.
 *
 * What the suite does not reach is marked NotTested on the method itself, so
 * it goes wherever the method goes, and the mark comes off the moment a test
 * reaches it.
 */
class CoverageTest extends TestCase {

    /**
     * Returns true if the given name is one of a Class, an Interface or a Trait
     * @param string $name
     * @return bool
     */
    private static function typeExists(string $name): bool {
        return class_exists($name) || interface_exists($name) || trait_exists($name);
    }

    /**
     * Returns the Type the given Test file is named for, or an empty string.
     * The file sits where the type does, except for the ones an inner folder
     * holds, as the Type and the Attr ones, which the tests group with the rest
     * @param string $relative
     * @return string
     */
    private static function typeOf(string $relative): string {
        $name  = (string)preg_replace('/(Live)?Test\.php$/', "", $relative);
        $exact = "Framework\\" . str_replace("/", "\\", $name);
        if (self::typeExists($exact)) {
            return $exact;
        }

        $parts = explode("/", $name);
        $first = $parts[0];
        $last  = $parts[count($parts) - 1];
        foreach ((array)glob(dirname(__DIR__, 2) . "/src/$first/*/$last.php") as $path) {
            $inner = basename(dirname((string)$path));
            $found = "Framework\\$first\\$inner\\$last";
            if (self::typeExists($found)) {
                return $found;
            }
        }
        return "";
    }

    /**
     * Returns the Test files of each type that has one, by the name of the type.
     * A type is tested by the file named after it, and by the Live one beside it
     * @return array<string,list<string>>
     */
    private static function testFilesByClass(): array {
        $result = [];
        $path   = dirname(__DIR__);

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            $filePath = (string)$file;
            if (!str_ends_with($filePath, "Test.php")) {
                continue;
            }

            $class = self::typeOf(substr($filePath, strlen($path) + 1));
            if ($class !== "") {
                $result[$class][] = $filePath;
            }
        }

        ksort($result);
        return $result;
    }

    /**
     * Returns the name the KnownGaps and the cases of a provider are keyed by
     * @param string $class
     * @return string
     */
    private static function shortNameOf(string $class): string {
        return str_replace("Framework\\", "", $class);
    }

    /**
     * Returns the public methods the given class declares as its own
     * @param string $class
     * @return list<string>
     */
    private static function ownMethodsOf(string $class): array {
        $reflection = new ReflectionClass($class);

        // The ones of a trait belong to whoever tests the trait
        $fromTrait = [];
        foreach ($reflection->getTraits() as $trait) {
            foreach ($trait->getMethods() as $method) {
                $fromTrait[$method->getName()] = true;
            }
        }

        $result = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            // The cases() of an enum is written by the engine, not by the class
            if ($method->isInternal() || isset($fromTrait[$name]) || str_starts_with($name, "__")) {
                continue;
            }
            $result[] = $name;
        }
        return $result;
    }

    /**
     * Returns the methods of the given class marked as one the suite does not reach
     * @param string $class
     * @return list<string>
     */
    private static function markedMethodsOf(string $class): array {
        $result = [];
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // A class below the one holding the mark inherits it, and is not the one marked
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }
            if (count($method->getAttributes(NotTested::class)) > 0) {
                $result[] = $method->getName();
            }
        }
        return $result;
    }

    /**
     * Returns the methods of the given class that none of its Test files calls
     * @param string $class
     * @return list<string>
     */
    private static function unreachedMethodsOf(string $class): array {
        $source = "";
        foreach (self::testFilesByClass()[$class] ?? [] as $path) {
            $source .= (string)file_get_contents($path);
        }

        $result = [];
        foreach (self::ownMethodsOf($class) as $method) {
            if (preg_match('/\b' . preg_quote($method, "/") . '\s*\(/', $source) !== 1) {
                $result[] = $method;
            }
        }
        return $result;
    }



    #[DataProvider("providerTestedClasses")]
    public function testEveryMethodIsReached(string $class): void {
        $unreached = self::unreachedMethodsOf($class);
        $marked    = self::markedMethodsOf($class);
        $missing   = array_values(array_diff($unreached, $marked));
        $names     = implode("(), ", $missing);

        $this->assertSame(
            [],
            $missing,
            "Nothing in the tests of $class calls $names(), so test it or mark it NotTested",
        );
    }

    /**
     * One case per class that has a Test file of its own
     * @return array<string,array{string}>
     */
    public static function providerTestedClasses(): array {
        $result = [];
        foreach (array_keys(self::testFilesByClass()) as $class) {
            $result[self::shortNameOf($class)] = [ $class ];
        }

        // A provider returning nothing would leave the check unmade
        if (count($result) === 0) {
            throw new AssertionFailedError("No class with a test file was found");
        }
        return $result;
    }


    // A mark left on a method a test reaches would excuse whatever it grows into
    #[DataProvider("providerMarkedMethods")]
    public function testTheMarkedAreNotReached(string $class, string $method): void {
        $this->assertTrue(
            in_array($method, self::unreachedMethodsOf($class), strict: true),
            "$class::$method() is reached now, so take the NotTested off it",
        );
    }

    /**
     * One case per method marked as one the suite does not reach
     * @return array<string,array{string,string}>
     */
    public static function providerMarkedMethods(): array {
        $result = [];
        foreach (array_keys(self::testFilesByClass()) as $class) {
            $name = self::shortNameOf($class);
            foreach (self::markedMethodsOf($class) as $method) {
                $result["$name::$method"] = [ $class, $method ];
            }
        }
        return $result;
    }
}
