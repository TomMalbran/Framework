<?php
namespace Tests\Tools;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The suite itself
 *
 * The config names one directory at a time, so a new one is not run until it
 * is listed, and nothing says so: the suite goes green with a whole folder
 * of tests never loaded.
 */
class SuiteTest extends TestCase {

    #[DataProvider("providerTestDirectories")]
    public function testEveryDirectoryIsRun(string $name): void {
        $config = (string)file_get_contents(dirname(__DIR__, 2) . "/phpunit.xml.dist");

        $this->assertStringContainsString("<directory>tests/$name</directory>", $config);
    }

    /**
     * One case per directory under the tests
     * @return array<string,array{string}>
     */
    public static function providerTestDirectories(): array {
        $result = [];
        foreach ((array)glob(dirname(__DIR__) . "/*", GLOB_ONLYDIR) as $path) {
            $name          = basename((string)$path);
            $result[$name] = [ $name ];
        }

        // A provider returning nothing would leave the check unmade
        if (count($result) === 0) {
            throw new AssertionFailedError("No test directory was found");
        }
        return $result;
    }
}
