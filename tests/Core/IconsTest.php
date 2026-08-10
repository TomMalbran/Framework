<?php
namespace Tests\Core;

use Framework\Application;
use Framework\Core\Icons;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionMethod;

/**
 * The Icons, which build a stylesheet per set and a page to browse them
 */
class IconsTest extends TestCase {
    use TestHelpers;

    /** @var array<string,mixed> */
    private array $wasSet = [];


    protected function setUp(): void {
        // Every one of these is static and set from a config file, so each
        // test has to put back what it found
        foreach ([ "title", "sourcePath", "previewPath", "mappingPath", "iconSets" ] as $name) {
            $this->wasSet[$name] = $this->getPrivateStaticProperty(Icons::class, $name);
        }
    }

    protected function tearDown(): void {
        foreach ($this->wasSet as $name => $value) {
            $this->setPrivateStaticProperty(Icons::class, $name, $value);
        }
    }

    /**
     * Calls one of the private methods of the Icons
     * @param string $method
     * @param mixed  ...$args
     * @return mixed
     */
    private function call(string $method, mixed ...$args): mixed {
        return (new ReflectionMethod(Icons::class, $method))->invoke(null, ...$args);
    }



    public function testTheTitleIsTheNameOfTheProjectWhenNoneIsGiven(): void {
        $this->assertSame(Application::getName(), $this->call("getTitle"));
    }

    public function testTheTitleGivenIsTheOneTheePageCarries(): void {
        // Composer requires the package name to be lowercase, so an acronym
        // comes back title cased and can only be spelled out here
        Icons::setTitle("ACME");

        $this->assertSame("ACME", $this->call("getTitle"));
    }

    public function testATitleThatIsEmptyLeavesTheNameOfTheProject(): void {
        Icons::setTitle("");

        $this->assertSame(Application::getName(), $this->call("getTitle"));
    }



    /**
     * A set that is missing one of the three things it needs
     * @param string       $name
     * @param list<string> $folders
     * @param string       $stylePath
     * @return void
     */
    #[DataProvider("providerBadSet")]
    public function testASetThatIsNotWholeIsNotRegistered(
        string $name,
        array $folders,
        string $stylePath,
    ): void {
        $this->setPrivateStaticProperty(Icons::class, "iconSets", []);
        Icons::register($name, $folders, $stylePath);

        $this->assertSame([], $this->getPrivateStaticProperty(Icons::class, "iconSets"));
    }

    /**
     * @return array<string,array{string,list<string>,string}>
     */
    public static function providerBadSet(): array {
        return [
            "no name"       => [ "", [ "actions" ], "public/icons.css" ],
            "no folders"    => [ "admin", [], "public/icons.css" ],
            "no stylesheet" => [ "admin", [ "actions" ], "" ],
        ];
    }

    public function testAWholeSetIsRegisteredUnderItsName(): void {
        $this->setPrivateStaticProperty(Icons::class, "iconSets", []);
        Icons::register("admin", [ "actions", "social" ], "public/icons.css");

        $this->assertSame([
            "admin" => [
                "folders"   => [ "actions", "social" ],
                "stylePath" => "public/icons.css",
            ],
        ], $this->getPrivateStaticProperty(Icons::class, "iconSets"));
    }



    /**
     * A tag read off an icon name, and the title the page filters it under
     * @param string $name
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerFilterTitle")]
    public function testTheFilterIsTitledAfterWhatTheTagMeans(
        string $name,
        string $expected,
    ): void {
        $this->assertSame($expected, $this->call("getFilterTitle", $name));
    }

    /**
     * The weights and the sizes are read out of the tag, the known variants
     * are named, and anything else is shown as it was written
     * @return array<string,array{string,string}>
     */
    public static function providerFilterTitle(): array {
        return [
            "a weight"        => [ "w400", "Weight 400" ],
            "a size"          => [ "s24", "Size 24" ],
            "a variant"       => [ "fill", "Filled" ],
            "a source"        => [ "google", "Google" ],
            "another one"     => [ "fontAwesome", "FontAwesome" ],
            "a w that is not" => [ "wide", "Wide" ],
        ];
    }

    public function testTheFiltersComeBackWithTheMostUsedFirst(): void {
        /** @var list<array{group:string,name:string,title:string,amount:int}> */
        $result = $this->call("getFilters", "Sources", [ "custom" => 2, "google" => 9, "fill" => 5 ]);

        $this->assertSame([ "google", "fill", "custom" ], array_column($result, "name"));
        $this->assertSame([ "Google", "Filled", "Custom" ], array_column($result, "title"));
        $this->assertSame([ 9, 5, 2 ], array_column($result, "amount"));
        $this->assertSame([ "Sources", "Sources", "Sources" ], array_column($result, "group"));
    }

    public function testAnIconIsInlinedAsADataUrl(): void {
        $svg    = "<svg   xmlns=\"http://www.w3.org/2000/svg\">\n  <path d=\"M0 0\"/>\n</svg>";
        $result = $this->call("getDataUrl", $svg);

        $this->assertIsString($result);
        $this->assertStringStartsWith("data:image/svg+xml;base64,", $result);

        $decoded = base64_decode(substr($result, strlen("data:image/svg+xml;base64,")));
        $this->assertSame("<svg xmlns=\"http://www.w3.org/2000/svg\"> <path d=\"M0 0\"/> </svg>", $decoded);
    }
}
