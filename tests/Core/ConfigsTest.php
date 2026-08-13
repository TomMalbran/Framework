<?php
namespace Tests\Core;

use Framework\Core\Configs;

use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Configs, the env files read into typed values
 *
 * The load reads the files of the repository once for the whole run, so the
 * data is put in place here rather than loaded, and the parsing of a line is
 * asked of the reader itself.
 */
class ConfigsTest extends TestCase {
    use TestHelpers;

    /** @var mixed */
    private mixed $data = null;


    protected function setUp(): void {
        Configs::load();
        $this->data = $this->getPrivateStaticProperty(Configs::class, "data");
    }

    protected function tearDown(): void {
        $this->setPrivateStaticProperty(Configs::class, "data", $this->data);
    }

    /**
     * Puts the given values in place of the ones of the repository
     * @param array<string,mixed> $data
     * @return void
     */
    private function useData(array $data): void {
        $this->setPrivateStaticProperty(Configs::class, "data", $data);
    }

    /**
     * Reads the given env file contents
     * @param string $contents
     * @return array<string,mixed>
     */
    private function readEnv(string $contents): array {
        $path = sys_get_temp_dir() . "/framework-config-test";
        if (!is_dir($path)) {
            mkdir($path);
        }
        file_put_contents("$path/.env.test", $contents);

        /** @var array<string,mixed> */
        return $this->callPrivateStaticMethod(Configs::class, "loadENV", $path, ".env.test");
    }



    /**
     * A line of an env file, and the value it is read as
     * @param string $line
     * @param mixed  $expected
     * @return void
     */
    #[DataProvider("providerLoadEnv")]
    public function testALineIsGivenItsType(string $line, mixed $expected): void {
        $this->assertSame([ "KEY" => $expected ], $this->readEnv($line));
    }

    /**
     * @return array<string,array{string,mixed}>
     */
    public static function providerLoadEnv(): array {
        return [
            "a true"      => [ "KEY = true", true ],
            "a false"     => [ "KEY = false", false ],
            "a string"    => [ "KEY = \"a value\"", "a value" ],
            "an empty"    => [ "KEY = \"\"", "" ],
            "a float"     => [ "KEY = 1.5", 1.5 ],
            "an int"      => [ "KEY = 42", 42 ],
            "a zero"      => [ "KEY = 0", 0 ],
            "a list"      => [ "KEY = [\"a\", \"b\"]", [ "a", "b" ] ],
            "empty list"  => [ "KEY = []", [] ],
        ];
    }

    /**
     * A line of an env file that is not one, and so is skipped
     * @param string $contents
     * @return void
     */
    #[DataProvider("providerSkipped")]
    public function testALineThatIsNotOneIsSkipped(string $contents): void {
        $this->assertSame([], $this->readEnv($contents));
    }

    /**
     * @return array<string,array{string}>
     */
    public static function providerSkipped(): array {
        return [
            "nothing"       => [ "" ],
            "a blank line"  => [ "   " ],
            "a comment"     => [ "# KEY = 1" ],
            "no separator"  => [ "KEY=1" ],
            "two of them"   => [ "KEY = 1 = 2" ],
        ];
    }

    public function testTheLinesAreReadInOrder(): void {
        $result = $this->readEnv("# A comment\nONE = 1\n\nTWO = \"two\"\n");

        $this->assertSame([ "ONE" => 1, "TWO" => "two" ], $result);
    }

    public function testAFileThatIsNotThereIsEmpty(): void {
        $result = $this->callPrivateStaticMethod(
            Configs::class,
            "loadENV",
            sys_get_temp_dir(),
            ".env.nothing",
        );

        $this->assertSame([], $result);
    }



    public function testAPropertyIsFoundByItsName(): void {
        // The name is written in camel case and the key in constant case
        $this->useData([ "SOME_VALUE" => "a value" ]);

        $this->assertSame("a value", Configs::getString("someValue"));
    }

    public function testAStringHasADefault(): void {
        $this->useData([]);

        $this->assertSame("", Configs::getString("nothing"));
        $this->assertSame("a default", Configs::getString("nothing", "a default"));
    }

    /**
     * A value, and whether it is read as true
     * @param mixed $value
     * @param bool  $expected
     * @return void
     */
    #[DataProvider("providerBoolean")]
    public function testABooleanIsWhatIsThere(mixed $value, bool $expected): void {
        $this->useData([ "KEY" => $value ]);

        $this->assertSame($expected, Configs::getBoolean("key"));
    }

    /**
     * @return array<string,array{mixed,bool}>
     */
    public static function providerBoolean(): array {
        return [
            "a true"     => [ true, true ],
            "a false"    => [ false, false ],
            "a one"      => [ 1, true ],
            "a zero"     => [ 0, false ],
            "a string"   => [ "a value", true ],
            "an empty"   => [ "", false ],
        ];
    }

    public function testAnIntIsANumber(): void {
        $this->useData([ "KEY" => "42" ]);

        $this->assertSame(42, Configs::getInt("key"));
    }

    public function testAnIntHasADefault(): void {
        $this->useData([]);

        $this->assertSame(0, Configs::getInt("nothing"));
        $this->assertSame(7, Configs::getInt("nothing", 7));
    }

    public function testAFloatIsANumber(): void {
        $this->useData([ "KEY" => "1.5" ]);

        $this->assertSame(1.5, Configs::getFloat("key"));
    }

    public function testAFloatHasADefault(): void {
        $this->useData([]);

        $this->assertSame(0.0, Configs::getFloat("nothing"));
        $this->assertSame(2.5, Configs::getFloat("nothing", 2.5));
    }

    /**
     * A value, and the list it is read as
     * @param mixed        $value
     * @param list<string> $expected
     * @return void
     */
    #[DataProvider("providerList")]
    public function testAListIsAlwaysOne(mixed $value, array $expected): void {
        $this->useData([ "KEY" => $value ]);

        $this->assertSame($expected, Configs::getList("key"));
    }

    /**
     * @return array<string,array{mixed,list<string>}>
     */
    public static function providerList(): array {
        return [
            "a list"      => [ [ "a", "b" ], [ "a", "b" ] ],
            "one split"   => [ "a,b", [ "a", "b" ] ],
            "one on its own" => [ "a", [ "a" ] ],
            "one of ints" => [ [ 1, 2 ], [ "1", "2" ] ],
        ];
    }

    public function testAListThatIsNotThereIsEmpty(): void {
        $this->useData([]);

        $this->assertSame([], Configs::getList("nothing"));
    }



    public function testTheDataIsWhatWasRead(): void {
        $this->useData([ "KEY" => "a value" ]);

        $this->assertSame([ "KEY" => "a value" ], Configs::getData());
    }

    public function testThereIsAnEnvironment(): void {
        // This repository is read without one of the .env.* files, so it is
        // the local one it falls back to
        $this->assertSame("local", Configs::getEnvironment());
        $this->assertIsArray(Configs::getEnvironments());
    }
}
