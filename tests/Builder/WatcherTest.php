<?php
namespace Tests\Builder;

use Framework\Builder\Watcher;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

class WatcherTest extends TestCase {
    use TestHelpers;

    private string $tmpDir = "";


    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "watcher_test_" . uniqid();
        @mkdir($this->tmpDir);
    }

    protected function tearDown(): void {
        Storage::deleteDir($this->tmpDir);
    }

    /**
     * Invokes one of the private static helpers
     */
    private function invokeWatcher(string $method, mixed ...$args): mixed {
        return (new ReflectionMethod(Watcher::class, $method))->invoke(null, ...$args);
    }

    private function writeGitignore(string $content): void {
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . ".gitignore", $content);
    }


    #[DataProvider("providerHasChanges")]
    public function testHasChanges(array $previousState, array $currentState, bool $expected): void {
        $result = $this->invokeWatcher("hasChanges", $previousState, $currentState);
        $this->assertSame($expected, $result);
    }

    public static function providerHasChanges(): array {
        return [
            "unchanged"        => [ [ "a.php" => 100 ], [ "a.php" => 100 ], false ],
            "both_empty"       => [ [], [], false ],
            "modified"         => [ [ "a.php" => 100 ], [ "a.php" => 200 ], true ],
            "added"            => [ [ "a.php" => 100 ], [ "a.php" => 100, "b.php" => 100 ], true ],
            "deleted"          => [ [ "a.php" => 100, "b.php" => 100 ], [ "a.php" => 100 ], true ],
            "first_scan"       => [ [], [ "a.php" => 100 ], true ],
            "all_deleted"      => [ [ "a.php" => 100 ], [], true ],
            "renamed"          => [ [ "a.php" => 100 ], [ "b.php" => 100 ], true ],
            "many_unchanged"   => [
                [ "a.php" => 100, "b.php" => 200 ],
                [ "a.php" => 100, "b.php" => 200 ],
                false,
            ],
            "one_of_many_changed" => [
                [ "a.php" => 100, "b.php" => 200 ],
                [ "a.php" => 100, "b.php" => 201 ],
                true,
            ],
        ];
    }


    #[DataProvider("providerParseGitignore")]
    public function testParseGitignore(array $lines, array $expected): void {
        $this->writeGitignore(implode("\n", $lines));
        $this->assertSame($expected, $this->invokeWatcher("parseGitignore", $this->tmpDir));
    }

    public static function providerParseGitignore(): array {
        return [
            "plain_name"       => [ [ "vendor" ], [ "/vendor/" ] ],
            "wildcard"         => [ [ "*.log" ], [ '/.*\.log/' ] ],
            "single_char"      => [ [ "temp?" ], [ "/temp./" ] ],
            "anchored_to_root" => [ [ "/build" ], [ "/^build/" ] ],
            "directory_only"   => [ [ "cache/" ], [ '/cache\/$/' ] ],
            "skips_comments"   => [ [ "# a comment", "vendor" ], [ "/vendor/" ] ],
            "skips_blanks"     => [ [ "", "vendor", "" ], [ "/vendor/" ] ],
            "multiple"         => [ [ "vendor", "*.log" ], [ "/vendor/", '/.*\.log/' ] ],
            // The ** glob is not expanded: the single-* replacement runs first
            "double_star"      => [ [ "**/nested" ], [ '/.*.*\/nested/' ] ],
        ];
    }


    public function testParseGitignoreWithoutFile(): void {
        $this->assertSame([], $this->invokeWatcher("parseGitignore", $this->tmpDir));
    }


    #[DataProvider("providerIsIgnored")]
    public function testIsIgnored(string $filePath, array $patterns, bool $expected): void {
        $result = $this->invokeWatcher("isIgnored", $filePath, "/app", $patterns);
        $this->assertSame($expected, $result);
    }

    public static function providerIsIgnored(): array {
        return [
            "matches_directory" => [ "/app/vendor/x.php", [ "/vendor/" ], true ],
            "matches_wildcard"  => [ "/app/debug.log", [ '/.*\.log/' ], true ],
            "no_match"          => [ "/app/src/a.php", [ "/vendor/" ], false ],
            "no_patterns"       => [ "/app/src/a.php", [], false ],
            "second_pattern"    => [ "/app/debug.log", [ "/vendor/", '/.*\.log/' ], true ],
            // The pattern is not anchored, so it also matches inside a file name
            "matches_substring" => [ "/app/src/vendor.txt", [ "/vendor/" ], true ],
        ];
    }


    public function testScanDirectoryReturnsModifiedTimes(): void {
        @mkdir($this->tmpDir . DIRECTORY_SEPARATOR . "sub");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "a.php", "a");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "sub" . DIRECTORY_SEPARATOR . "b.php", "b");

        $result = $this->invokeWatcher("scanDirectory", $this->tmpDir, $this->tmpDir, []);

        $this->assertCount(2, $result);
        foreach ($result as $file => $mtime) {
            $this->assertFileExists($file);
            $this->assertIsInt($mtime);
        }
    }


    public function testScanDirectorySkipsIgnoredFiles(): void {
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "keep.php", "a");
        @file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . "skip.log", "b");

        $result = $this->invokeWatcher("scanDirectory", $this->tmpDir, $this->tmpDir, [ '/.*\.log/' ]);
        $names  = array_map(fn($file) => basename((string)$file), array_keys($result));

        $this->assertSame([ "keep.php" ], $names);
    }


    public function testScanDirectoryOnEmptyDirectory(): void {
        $this->assertSame([], $this->invokeWatcher("scanDirectory", $this->tmpDir, $this->tmpDir, []));
    }
}
