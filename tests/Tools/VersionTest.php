<?php
namespace Tests\Tools;

use Framework\Application;
use Framework\Discovery\Package;
use Framework\Tools\Version;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Framework Version command
 *
 * Only the paths that write nothing are covered here. Moving the version
 * rewrites composer.json, the README and every documentation page, which is
 * not something a test run should leave behind.
 */
class VersionTest extends TestCase {

    public function testTheVersionIsPrinted(): void {
        $this->expectOutputString("Version: " . Application::getVersion() . "\n");

        Version::getVersion();
    }

    public function testTheApplicationAndThePackageAgreeOnIt(): void {
        // They read the same composer file here, since the Framework is the app
        $this->assertSame(Application::getVersion(), Package::getVersion());
    }

    /**
     * A version the command does take, which it then finds is the one already set
     * @param string $version
     * @return void
     */
    #[DataProvider("providerGoodVersions")]
    public function testAVersionOfThreeNumbersIsTaken(string $version): void {
        // Trimmed and compared, and nothing is written when it has not moved
        $this->expectOutputString("The version is already " . Application::getVersion() . "\n");

        Version::setVersion($version);
    }

    /**
     * The version the package is at, written the ways the command accepts
     * @return array<string,array{string}>
     */
    public static function providerGoodVersions(): array {
        $version = Application::getVersion();

        return [
            "as it is"       => [ $version ],
            "spaced before"  => [ "  $version" ],
            "spaced after"   => [ "$version  " ],
            "spaced on both" => [ "  $version  " ],
            "with a newline" => [ "$version\n" ],
        ];
    }

    /**
     * A version that is not x.y.z is refused before anything is written
     * @param string $version
     * @return void
     */
    #[DataProvider("providerBadVersions")]
    public function testAVersionThatIsNotThreeNumbersIsRefused(string $version): void {
        $this->expectOutputString("The version must be given as x.y.z\n");

        Version::setVersion($version);
    }

    /**
     * The ways of writing a version the command does not take
     * @return array<string,array{string}>
     */
    public static function providerBadVersions(): array {
        return [
            "two parts"   => [ "0.17" ],
            "four parts"  => [ "0.17.0.1" ],
            "a word"      => [ "next" ],
            "a leading v" => [ "v0.17.0" ],
            "a suffix"    => [ "0.17.0-beta" ],
            "letters"     => [ "0.x.0" ],
        ];
    }
}
