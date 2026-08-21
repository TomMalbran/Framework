<?php
namespace Tests\Framework;

use Framework\Application;
use Framework\Discovery\Type\ComposerData;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Application, and the Libraries the composer file of an App records
 */
class ApplicationTest extends TestCase {
    use TestHelpers;

    private ?ComposerData $composerWas = null;

    protected function tearDown(): void {
        // The composer data is read once and kept, so the rest of the suite
        // gets back whatever was already there
        $this->swapComposer($this->composerWas);
    }



    /**
     * The Version asked for against the Libraries the App copied in
     * @param string $name
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerLibraryVersion")]
    public function testTheLibraryVersion(string $name, string $expected): void {
        $this->composerWas = $this->swapComposer(new ComposerData(libraries: [
            "dashboard" => "1.2.0",
            "editor"    => "2.0.1",
        ]));

        $this->assertSame($expected, Application::getLibraryVersion($name));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerLibraryVersion(): array {
        return [
            "one that is copied" => [ "dashboard", "1.2.0" ],
            "another one"        => [ "editor", "2.0.1" ],
            "one nobody copied"  => [ "admin", "" ],
            "nothing asked for"  => [ "", "" ],
        ];
    }

    public function testTheFrameworkCopiesNoLibrary(): void {
        // The App here is the Framework itself, and it is the library
        $this->assertSame([], Application::getComposer()->libraries);
        $this->assertSame("", Application::getLibraryVersion("dashboard"));
    }
}
