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
     * The version a move lands on, or empty when it cannot move there
     * @param string $version
     * @param int    $amount
     * @param bool   $patch
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerMoveVersion")]
    public function testTheVersionMovesWhereItIsAsked(
        string $version,
        int $amount,
        bool $patch,
        string $expected,
    ): void {
        $this->assertSame($expected, Version::moveVersion($version, $amount, patch: $patch));
    }

    /**
     * The minor takes the patch back to zero with it, which is what a release
     * has always done. The patch moves on its own when it is asked for.
     * @return array<string,array{string,int,bool,string}>
     */
    public static function providerMoveVersion(): array {
        return [
            "the minor up"          => [ "0.17.0", 1, false, "0.18.0" ],
            "the minor down"        => [ "0.18.0", -1, false, "0.17.0" ],
            "the minor over ten"    => [ "0.9.0", 1, false, "0.10.0" ],
            "the minor keeps major" => [ "2.4.0", 1, false, "2.5.0" ],
            "the minor drops patch" => [ "1.2.3", 1, false, "1.3.0" ],
            "the minor at zero"     => [ "1.0.0", -1, false, "" ],

            "the patch up"          => [ "0.17.0", 1, true, "0.17.1" ],
            "the patch down"        => [ "0.17.1", -1, true, "0.17.0" ],
            "the patch over ten"    => [ "0.17.9", 1, true, "0.17.10" ],
            "the patch keeps minor" => [ "1.2.3", 1, true, "1.2.4" ],
            "the patch at zero"     => [ "0.17.0", -1, true, "" ],

            "two parts"             => [ "0.17", 1, false, "" ],
            "four parts"            => [ "0.17.0.1", 1, false, "" ],
            "nothing at all"        => [ "", 1, false, "" ],
        ];
    }
}
