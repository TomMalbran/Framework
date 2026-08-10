<?php
namespace Tests\Tools;

use Framework\Discovery\Package;
use Framework\Tools\Release;
use Framework\Utils\JSON;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionMethod;

/**
 * The Release command
 *
 * Only what can be asked without releasing anything. Everything past the guards
 * writes the version, commits, tags and moves a branch, which a test run has no
 * business doing; that half was checked by releasing inside a throwaway clone.
 */
class ReleaseTest extends TestCase {

    /**
     * Calls one of the private methods the command is built from
     * @param string $method
     * @param mixed  ...$args
     * @return mixed
     */
    private function call(string $method, mixed ...$args): mixed {
        $reflection = new ReflectionMethod(Release::class, $method);
        return $reflection->invoke(null, ...$args);
    }



    /**
     * The tag written for a version
     * @param string $version
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerTagFor")]
    public function testTheTagLeadsWithAV(string $version, string $expected): void {
        $this->assertSame($expected, $this->call("tagFor", $version));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerTagFor(): array {
        return [
            "a version"      => [ "0.17.0", "v0.17.0" ],
            "the next one"   => [ "0.18.0", "v0.18.0" ],
            "nothing at all" => [ "", "v" ],
        ];
    }



    public function testTheVersionIsReadFreshFromTheComposerFile(): void {
        // Not Application::getVersion(), which keeps the first answer it gave and
        // so cannot see the version the command just wrote
        $composer = JSON::readFile(Package::getBasePath(), "composer.json");

        $this->assertSame($composer["version"], $this->call("currentVersion"));
    }

    public function testATagThatIsNotThereIsNotFound(): void {
        $this->assertFalse($this->call("hasTag", "v0.0.0-not-a-tag"));
    }

    public function testGitRunsInTheRepository(): void {
        /** @var array{int,list<string>} */
        $result = $this->call("git", "rev-parse", "--is-inside-work-tree");

        $this->assertSame(0, $result[0]);
        $this->assertSame("true", $result[1][0] ?? "");
    }

    public function testGitSaysWhenItFailed(): void {
        /** @var array{int,list<string>} */
        $result = $this->call("git", "not-a-git-command");

        $this->assertNotSame(0, $result[0]);
        $this->assertNotEmpty($result[1]);
    }
}
