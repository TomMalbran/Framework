<?php
namespace Tests\Discovery;

use Framework\Application;
use Framework\Discovery\Attr\Priority;
use Framework\Discovery\Discovery;
use Framework\Discovery\Package;
use Framework\Discovery\Type\DiscoveryBuilder;
use Framework\Database\Where\BaseWhere;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

use Tests\Discovery\Fixture\BaseThing;
use Tests\Discovery\Fixture\Thing;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * The Discovery
 *
 * The classes it walks are the Framework's own, which is the only source there
 * is when the tests run from inside the Framework.
 */
class DiscoveryTest extends TestCase {
    use TestHelpers;

    /** @var list<string>|null */
    private static ?array $frameworkClasses = null;


    /**
     * Returns the names of every Class the Discovery finds, walked once
     * @return list<string>
     */
    private function frameworkClasses(): array {
        if (self::$frameworkClasses === null) {
            $result = [];
            foreach (Discovery::findClasses(forFramework: true) as $class) {
                $result[] = $class->getName();
            }
            self::$frameworkClasses = $result;
        }
        return self::$frameworkClasses;
    }

    /**
     * Runs the given callback with the Application rooted at another directory
     * @param string   $baseDir
     * @param callable $callback
     * @return void
     */
    private function withBaseDir(string $baseDir, callable $callback): void {
        /** @var string */
        $previous = $this->getPrivateStaticProperty(Application::class, "baseDir");
        $this->setPrivateStaticProperty(Application::class, "baseDir", $baseDir);

        try {
            $callback();
        } finally {
            $this->setPrivateStaticProperty(Application::class, "baseDir", $previous);
        }
    }



    /**
     * A class the Discovery is expected to find, or to leave alone
     * @param string $className
     * @param bool   $expected
     * @return void
     */
    #[DataProvider("providerFoundClasses")]
    public function testTheFrameworkClassesAreFound(string $className, bool $expected): void {
        $this->assertSame($expected, in_array($className, $this->frameworkClasses(), true));
    }

    /**
     * The interfaces are left out because the check that gates the walk asks
     * for a class line, and the generated directories because the build writes them
     * @return array<string,array{string,bool}>
     */
    public static function providerFoundClasses(): array {
        return [
            "a class"          => [ Discovery::class, true ],
            "another class"    => [ Package::class, true ],
            "a nested class"   => [ "Framework\\Discovery\\Type\\DiscoveryClass", true ],
            "an interface"     => [ DiscoveryBuilder::class, false ],
            "a generated one"  => [ "Framework\\System\\Config", false ],
            "a schema one"     => [ "Framework\\Auth\\Schema\\CredentialSchema", false ],
            "a test fixture"   => [ Thing::class, false ],
            "one that is gone" => [ "Framework\\NotAClass", false ],
        ];
    }

    public function testThereAreEnoughOfThem(): void {
        $this->assertGreaterThan(100, count($this->frameworkClasses()));
    }

    public function testTheGeneratedDirectoriesAreLeftOut(): void {
        // Walking them would find whatever the last build happened to leave behind
        foreach ($this->frameworkClasses() as $className) {
            $this->assertFalse(
                Strings::contains($className, [ "\\Schema\\", "\\System\\" ]),
                "$className should not have been discovered",
            );
        }
    }

    public function testTheClassesComeBackInPriorityOrder(): void {
        $classes    = Discovery::findClasses(forFramework: true);
        $previous   = Priority::Highest;
        $priorities = [];

        foreach ($classes as $class) {
            $priority = $class->getPriority();
            $this->assertGreaterThanOrEqual($previous, $priority);
            $previous = $priority;
            $priorities[$priority] = true;
        }

        // More than one priority, or the order above would prove nothing
        $this->assertGreaterThan(1, count($priorities));
    }



    /**
     * The classes found when the walk is narrowed
     * @param string      $path
     * @param string|null $parentClass
     * @param string|null $interface
     * @param string      $prefix
     * @param bool        $isEmpty
     * @return void
     */
    #[DataProvider("providerNarrowed")]
    public function testOnlyTheClassesAskedForAreFound(
        string $path,
        ?string $parentClass,
        ?string $interface,
        string $prefix,
        bool $isEmpty,
    ): void {
        $classes = Discovery::findClasses(
            parentClass: $parentClass,
            interface: $interface,
            path: $path === "" ? "" : Package::getSourcePath($path),
            forFramework: true,
        );

        $this->assertSame($isEmpty, count($classes) === 0);
        foreach ($classes as $class) {
            $className = $class->getName();
            if ($prefix !== "") {
                $this->assertStringStartsWith($prefix, $className);
            }
            if ($parentClass !== null) {
                $this->assertContains($parentClass, class_parents($className));
                $this->assertNotSame($parentClass, $className);
            }
            if ($interface !== null) {
                $this->assertContains($interface, class_implements($className));
            }
        }
    }

    /**
     * @return array<string,array{string,string|null,string|null,string,bool}>
     */
    public static function providerNarrowed(): array {
        return [
            "everything"         => [ "", null, null, "Framework\\", false ],
            "a path"             => [ "Discovery", null, null, "Framework\\Discovery\\", false ],
            "a deeper path"      => [ "Discovery/Attr", null, null, "Framework\\Discovery\\Attr\\", false ],
            "a path with none"   => [ "NotADirectory", null, null, "", true ],
            "an interface"       => [ "", null, DiscoveryBuilder::class, "", false ],
            "a parent class"     => [ "", BaseWhere::class, null, "", false ],
            "a path and parent"  => [ "Database", BaseWhere::class, null, "Framework\\Database\\", false ],
            "a parent with none" => [ "Discovery", BaseWhere::class, null, "", true ],
        ];
    }

    public function testTheUsesAreCarriedAlongWithTheClass(): void {
        $classes = Discovery::findClasses(
            path: Package::getSourcePath("Discovery"),
            forFramework: true,
        );
        $found   = null;
        foreach ($classes as $class) {
            if ($class->getName() === Discovery::class) {
                $found = $class;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame("Framework\\Utils\\Strings", $found->getUseClassName("Strings"));
    }

    public function testTheClassesOfBothAreFoundTogether(): void {
        $names = [];
        foreach (Discovery::findClasses(forAll: true) as $class) {
            $names[] = $class->getName();
        }

        // The two are merged by class name, so the Framework being its own app
        // here leaves one of each rather than two
        $this->assertSame($this->frameworkClasses(), $names);
    }



    /**
     * The properties of a class, and the ones a filter asks for
     * @param int|null             $filter
     * @param array<string,string> $expected
     * @return void
     */
    #[DataProvider("providerProperties")]
    public function testTheAskedForPropertiesAreReturned(?int $filter, array $expected): void {
        $thing = new Thing();

        $this->assertSame($expected, Discovery::getProperties($thing, $filter));
        $this->assertSame(array_keys($expected), Discovery::getPropertyNames($thing, $filter));
        $this->assertCount(count($expected), Discovery::getReflectionProps($thing, $filter));
    }

    /**
     * Without a filter the public and the protected ones are asked for
     * @return array<string,array{int|null,array<string,string>}>
     */
    public static function providerProperties(): array {
        return [
            "the default"   => [ null, [ "colour" => "string", "size" => "int", "id" => "int", "name" => "string" ] ],
            "the public"    => [ ReflectionProperty::IS_PUBLIC, [ "colour" => "string", "id" => "int", "name" => "string" ] ],
            "the protected" => [ ReflectionProperty::IS_PROTECTED, [ "size" => "int" ] ],
            "the private"   => [ ReflectionProperty::IS_PRIVATE, [ "hidden" => "bool" ] ],
        ];
    }

    public function testTheReflectionPropertiesAreTheOnesBehindThem(): void {
        $properties = Discovery::getReflectionProps(new Thing());

        $this->assertInstanceOf(ReflectionProperty::class, $properties[0]);
        $this->assertSame("colour", $properties[0]->getName());
    }



    /**
     * The priority read off a class or off a method
     * @param string      $className
     * @param string|null $method
     * @param int         $expected
     * @return void
     */
    #[DataProvider("providerPriority")]
    public function testThePriorityIsReadFromTheAttribute(
        string $className,
        ?string $method,
        int $expected,
    ): void {
        $reflection = $method === null
            ? new ReflectionClass($className)
            : new ReflectionMethod($className, $method);

        $this->assertSame($expected, Discovery::getPriority($reflection));
    }

    /**
     * @return array<string,array{string,string|null,int}>
     */
    public static function providerPriority(): array {
        return [
            "a class with it"    => [ Thing::class, null, Priority::High ],
            "a class without it" => [ BaseThing::class, null, Priority::Normal ],
            "a method"           => [ Thing::class, "isHidden", Priority::Normal ],
            "a constructor"      => [ Thing::class, "__construct", Priority::Normal ],
        ];
    }



    /**
     * A JSON file read from the base path
     * @param string $fileName
     * @param bool   $isEmpty
     * @return void
     */
    #[DataProvider("providerLoadJSON")]
    public function testTheJsonIsReadFromTheBasePath(string $fileName, bool $isEmpty): void {
        $data = Discovery::loadJSON("", $fileName);

        $this->assertSame($isEmpty, $data === []);
        if (!$isEmpty) {
            $this->assertSame(Package::getVersion(), $data["version"]);
        }
    }

    /**
     * The Framework is the Application while the tests run, so its own composer
     * file is the one at the base path, and the suffix is added when missing
     * @return array<string,array{string,bool}>
     */
    public static function providerLoadJSON(): array {
        return [
            "without the suffix" => [ "composer", false ],
            "with the suffix"    => [ "composer.json", false ],
            "one that is gone"   => [ "notAFile", true ],
            "one in a directory" => [ "src/notAFile", true ],
        ];
    }

    public function testEveryValueOfTheCustomDataIsADictionary(): void {
        $data = Discovery::loadCustomData("composer");

        $this->assertArrayHasKey("autoload", $data);
        $this->assertInstanceOf(Dictionary::class, $data["autoload"]);
        $this->assertArrayHasKey("Framework\\", $data["autoload"]->getArray("psr-4"));
    }

    public function testCustomDataThatIsNotThereIsEmpty(): void {
        $this->assertSame([], Discovery::loadCustomData("notAFile"));
    }



    public function testTheEmailTemplateIsReadFromTheApp(): void {
        $this->assertStringContainsString(
            "{{{message}}}",
            Discovery::loadEmailTemplate("data/email.html"),
        );
    }

    public function testTheFrameworkTemplateIsUsedWhenTheAppHasNone(): void {
        // Pointed at a directory holding no template, so it has to fall back
        $this->withBaseDir(Package::DocsDir, function (): void {
            $this->assertFileDoesNotExist(Application::getBasePath("data/email.html"));
            $this->assertStringContainsString(
                "{{{message}}}",
                Discovery::loadEmailTemplate("data/email.html"),
            );
        });
    }

    public function testATemplateNeitherOfThemHasIsEmpty(): void {
        $this->assertSame("", Discovery::loadEmailTemplate("data/notATemplate.html"));
    }



    /**
     * A file the walk either reflects on or passes over
     * @param string               $contents
     * @param array<string,string> $usedClasses
     * @param array<string,string> $classPaths
     * @param bool                 $expected
     * @return void
     */
    #[DataProvider("providerIsValidClass")]
    public function testTheFilesWorthReflectingOnAreTold(
        string $contents,
        array $usedClasses,
        array $classPaths,
        bool $expected,
    ): void {
        $method = new ReflectionMethod(Discovery::class, "isValidClass");

        $this->assertSame($expected, $method->invoke(null, $contents, $usedClasses, $classPaths));
    }

    /**
     * A parent or a trait of the same namespace has to be one of the files
     * found, since a class whose own is missing cannot be reflected on
     * @return array<string,array{string,array<string,string>,array<string,string>,bool}>
     */
    public static function providerIsValidClass(): array {
        $plain    = "<?php\nnamespace Tests;\nclass Thing {\n}\n";
        $extends  = "<?php\nnamespace Tests;\nclass Thing extends Base {\n}\n";
        $withUse  = "<?php\nnamespace Tests;\nclass Thing extends Base {\n    use Helper;\n}\n";

        return [
            "no namespace"       => [ "<?php\nclass Alone {\n}\n", [], [], false ],
            "no class"           => [ "<?php\nnamespace Tests;\ninterface Thing {\n}\n", [], [], false ],
            "an enum"            => [ "<?php\nnamespace Tests;\nenum Thing {\n}\n", [], [], false ],
            "a trait"            => [ "<?php\nnamespace Tests;\ntrait Thing {\n}\n", [], [], false ],
            "nothing at all"     => [ "", [], [], false ],
            "no parent"          => [ $plain, [], [], true ],

            "a parent not found" => [ $extends, [ "Base" => "Tests\\Other\\Base" ], [], false ],
            "a parent found"     => [ $extends, [ "Base" => "Tests\\Other\\Base" ], [ "Tests\\Other\\Base" => "a.php" ], true ],
            "a parent elsewhere" => [ $extends, [ "Base" => "Vendor\\Base" ], [], true ],
            "a parent unlisted"  => [ $extends, [], [], true ],

            "a trait not found"  => [ $withUse, [ "Base" => "Vendor\\Base", "Helper" => "Tests\\Helper" ], [], false ],
            "a trait found"      => [ $withUse, [ "Base" => "Vendor\\Base", "Helper" => "Tests\\Helper" ], [ "Tests\\Helper" => "h.php" ], true ],
            "a trait elsewhere"  => [ $withUse, [ "Base" => "Vendor\\Base", "Helper" => "Vendor\\Helper" ], [], true ],
        ];
    }


    /**
     * One case per public method of the class, so a new one is not left untested
     * @param string $method
     * @return void
     */
    #[DataProvider("providerPublicMethods")]
    public function testEveryMethodIsTested(string $method): void {
        $this->assertMethodIsTested($method);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function providerPublicMethods(): array {
        return self::publicMethodsOf(Discovery::class);
    }
}
