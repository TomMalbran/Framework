<?php
namespace Tests\Core;

use Framework\Application;
use Framework\Core\Icons;
use Framework\File\Storage;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionMethod;

/**
 * The Icons, which build a stylesheet per set and a page to browse them
 */
class IconsTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Core/.tmp_icons";
    private const SourceDir  = self::FixtureDir . "/svg";
    private const StylePath  = self::FixtureDir . "/icons.css";
    private const PreviewPath = self::FixtureDir . "/preview.html";

    /** @var array<string,mixed> */
    private array $wasSet = [];


    protected function setUp(): void {
        // Every one of these is static and set from a config file, so each
        // test has to put back what it found
        foreach ([ "title", "sourcePath", "previewPath", "mappingPath", "iconSets" ] as $name) {
            $this->wasSet[$name] = $this->getPrivateStaticProperty(Icons::class, $name);
        }
        $this->setPrivateStaticProperty(Icons::class, "iconSets", []);
        $this->setPrivateStaticProperty(Icons::class, "mappingPath", "");
        $this->setPrivateStaticProperty(Icons::class, "previewPath", "");
    }

    protected function tearDown(): void {
        foreach ($this->wasSet as $name => $value) {
            $this->setPrivateStaticProperty(Icons::class, $name, $value);
        }
        Storage::deleteDir(Application::getBasePath(self::FixtureDir));
    }

    /**
     * Writes the given icons, a file per name, and points the Icons at them
     * @param array<string,list<string>> $folders
     * @return void
     */
    private function writeIcons(array $folders): void {
        foreach ($folders as $folder => $names) {
            $path = Application::getBasePath(self::SourceDir, $folder);
            Storage::createDir($path);

            foreach ($names as $name) {
                Storage::writeFile(
                    "$path/$name",
                    "<svg xmlns=\"http://www.w3.org/2000/svg\">\n  <path d=\"M0 0\"/>\n</svg>",
                );
            }
        }
        Icons::setSource(self::SourceDir);
    }

    /**
     * Writes the mapping of the icons and points the Icons at it
     * @param string $contents
     * @return void
     */
    private function writeMapping(string $contents): void {
        $path = Application::getBasePath(self::FixtureDir);
        Storage::createDir($path);
        Storage::writeFile("$path/mapping.json", $contents);

        Icons::setMapping(self::FixtureDir . "/mapping.json");
    }

    /**
     * Generates the icons, keeping what it printed
     * @return string
     */
    private function generate(): string {
        ob_start();
        try {
            Icons::generate();
        } finally {
            $output = ob_get_clean();
        }
        return (string)$output;
    }

    /**
     * Returns the contents of one of the files that were written
     * @param string $path
     * @return string
     */
    private function readFile(string $path): string {
        return Storage::readFile(Application::getBasePath($path));
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

    public function testAStylesheetIsWritten(): void {
        $this->writeIcons([ "general" => [ "home.svg", "user.svg" ] ]);
        Icons::register("app", [ "general" ], self::StylePath);

        $output = $this->generate();

        $this->assertStringContainsString("Read 2 icons from the general folder", $output);
        $this->assertStringContainsString("Created the app stylesheet with 2 icons", $output);

        $style = $this->readFile(self::StylePath);
        $this->assertStringContainsString(".icon-home:before", $style);
        $this->assertStringContainsString(".icon-user:before", $style);
        $this->assertStringContainsString("data:image/svg+xml;base64,", $style);
    }

    public function testTheIconsAreSortedByName(): void {
        $this->writeIcons([ "general" => [ "user.svg", "home.svg" ] ]);
        Icons::register("app", [ "general" ], self::StylePath);
        $this->generate();

        $style = $this->readFile(self::StylePath);
        $this->assertLessThan(
            strpos($style, ".icon-user:before"),
            strpos($style, ".icon-home:before"),
        );
    }

    public function testOnlySvgFilesAreRead(): void {
        $this->writeIcons([ "general" => [ "home.svg", "notes.txt" ] ]);
        Icons::register("app", [ "general" ], self::StylePath);

        $this->assertStringContainsString("Read 1 icons", $this->generate());
    }

    public function testAnEmptyFileIsNotAnIcon(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        Storage::writeFile(Application::getBasePath(self::SourceDir, "general/empty.svg"), "");
        Icons::register("app", [ "general" ], self::StylePath);

        $this->assertStringContainsString("Read 1 icons", $this->generate());
    }

    public function testASetTakesSeveralFolders(): void {
        $this->writeIcons([
            "general" => [ "home.svg" ],
            "extra"   => [ "star.svg" ],
        ]);
        Icons::register("app", [ "general", "extra" ], self::StylePath);
        $this->generate();

        $style = $this->readFile(self::StylePath);
        $this->assertStringContainsString(".icon-home:before", $style);
        $this->assertStringContainsString(".icon-star:before", $style);
    }

    public function testEachSetGetsItsOwnStylesheet(): void {
        // The folder they share is only read once
        $this->writeIcons([
            "general" => [ "home.svg" ],
            "extra"   => [ "star.svg" ],
        ]);
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::register("admin", [ "general", "extra" ], self::FixtureDir . "/admin.css");

        $output = $this->generate();

        $this->assertSame(1, substr_count($output, "Read 1 icons from the general folder"));
        $this->assertStringNotContainsString(
            ".icon-star:before",
            $this->readFile(self::StylePath),
        );
        $this->assertStringContainsString(
            ".icon-star:before",
            $this->readFile(self::FixtureDir . "/admin.css"),
        );
    }

    public function testAFolderWithNoIconsSaysSo(): void {
        $this->writeIcons([ "general" => [ "home.svg" ], "extra" => [] ]);
        Icons::register("app", [ "general", "extra" ], self::StylePath);

        $this->assertStringContainsString(
            "There are no icons in the extra folder",
            $this->generate(),
        );
    }

    public function testThereIsNothingToGenerateWithoutASource(): void {
        Icons::setSource("");
        Icons::register("app", [ "general" ], self::StylePath);

        $this->assertStringContainsString("There is no source directory", $this->generate());
    }

    public function testThereIsNothingToGenerateWithoutASet(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);

        $this->assertStringContainsString("There are no icon sets", $this->generate());
    }



    public function testThePreviewIsWritten(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);
        Icons::setTitle("ACME");

        $this->assertStringContainsString("Created the preview page", $this->generate());

        $preview = $this->readFile(self::PreviewPath);
        $this->assertStringContainsString("ACME", $preview);
        $this->assertStringContainsString("icon-home", $preview);
        $this->assertStringContainsString("general", $preview);
    }

    public function testThereIsNoPreviewWithoutAPath(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        Icons::register("app", [ "general" ], self::StylePath);

        $this->assertStringNotContainsString("Created the preview page", $this->generate());
    }

    public function testTheMappingGivesTheIconsTheirTags(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        $this->writeMapping('{"general":{"home":"google home fill"}}');
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);
        $this->generate();

        $preview = $this->readFile(self::PreviewPath);
        $this->assertStringContainsString("google", $preview);

        // Only a Material icon links out to the page it came from
        $this->assertStringContainsString("fonts.google.com/icons?selected", $preview);
    }

    public function testAMappingTakesComments(): void {
        // It is written by hand, so a json file with them is read just the same
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        $this->writeMapping("// The icons of the app\n{\"general\":{\"home\":\"custom\"}}");
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);
        $this->generate();

        $this->assertStringContainsString("custom", $this->readFile(self::PreviewPath));
    }

    public function testAnIconWithNoMappingIsUntagged(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        $this->writeMapping('{"general":{"other":"google"}}');
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);
        $this->generate();

        $this->assertStringNotContainsString(
            "fonts.google.com",
            $this->readFile(self::PreviewPath),
        );
    }

    public function testAMappingThatIsNotAMapIsSkipped(): void {
        // It is written by hand, so a shape that is not the one expected is
        // stepped over rather than read as a folder
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        $this->writeMapping('{"general":"not a map","0":{"home":"google"}}');
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);

        $this->assertStringContainsString("Created the preview page", $this->generate());
        $this->assertStringNotContainsString(
            "fonts.google.com",
            $this->readFile(self::PreviewPath),
        );
    }

    public function testAMappingThatIsNotThereIsSkipped(): void {
        $this->writeIcons([ "general" => [ "home.svg" ] ]);
        Icons::setMapping(self::FixtureDir . "/nothing.json");
        Icons::register("app", [ "general" ], self::StylePath);
        Icons::setPreview(self::PreviewPath);

        $this->assertStringContainsString("Created the preview page", $this->generate());
    }
}
