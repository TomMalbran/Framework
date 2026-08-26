<?php
namespace Tests\Intl;

use Framework\Intl\NLSUsage;
use Framework\Application;
use Framework\File\Storage;
use Tests\TestHelpers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Language Keys used by the Source
 *
 * One source file of each kind is written to a directory and read the way the
 * check reads them, so what counts as a use, and what counts as asking for a
 * key by name, is asserted against the code that would be written.
 */
class NLSUsageTest extends TestCase {
    use TestHelpers;

    private const FixtureDir = "tests/Intl/.tmp_nls_usage";

    // The backend asks for a key through the methods that take one, and through
    // the errors of a request. What a property of its own holds is not a key
    private const SourceFile = <<<PHP
    <?php
    class UserController {
        protected static string \$idDbName = "USER_ID";

        public function create(Request \$request): Response {
            \$errors = new Errors();
            \$errors->name = "USER_ERROR_NAME";
            \$errors->add("email", "USER_ERROR_EMAIL");
            if (\$errors->has()) {
                return Response::error(\$errors);
            }

            print(NLS::getString("GENERAL_ACCEPT"));
            print(NLS::pluralize("PRODUCT_COUNT", 3));
            print(NLS::getString("USER_" . \$type));
            return Response::success("USER_SUCCESS_CREATE");
        }
    }
    PHP;

    // And an app asks for it in a call of its own, or hands it to an element
    private const ScriptFile = <<<JS
    import NLS from "Dashboard/Core/NLS";

    export function UserPage() {
        const title = NLS.get("GENERAL_CANCEL");
        const other = `AUTHORIZED_\${type}`;
        dispatch({ type : "USER_LOADING" });

        return <Button message="GENERAL_SAVE" action="DELETE" title={title} />;
    }
    JS;

    private string $fixtureBase = "";

    /** @var array<string,mixed> */
    private array $usage = [];


    protected function setUp(): void {
        $this->fixtureBase = Application::getBasePath(self::FixtureDir);
        Storage::createDir($this->fixtureBase);
        Storage::writeFile($this->fixtureBase . "/UserController.php", self::SourceFile);
        Storage::writeFile($this->fixtureBase . "/UserPage.jsx", self::ScriptFile);

        /** @var array<string,mixed> */
        $usage = NLSUsage::read([ $this->fixtureBase ], [], withCalls: true);
        $this->usage = $usage;
    }

    protected function tearDown(): void {
        Storage::deleteDir($this->fixtureBase);
    }



    /**
     * A key of a Language file, and whether the source uses it
     * @param string $key
     * @param bool   $isUsed
     * @return void
     */
    #[DataProvider("providerIsUsed")]
    public function testTheKeyIsUsed(string $key, bool $isUsed): void {
        $this->assertSame($isUsed, NLSUsage::isUsed($this->usage, $key));
    }

    /**
     * @return array<string,array{string,bool}>
     */
    public static function providerIsUsed(): array {
        return [
            "a call"           => [ "GENERAL_ACCEPT", true ],
            "an attribute"     => [ "GENERAL_SAVE", true ],
            "a name of its own" => [ "USER_LOADING", true ],
            // NLS::pluralize() is given the key both of them are written from
            "a plural pair"    => [ "PRODUCT_COUNT_SINGULAR", true ],
            "a single plural"  => [ "PRODUCT_COUNT_PLURAL", true ],
            // And a key the code completes is only ever named by its start
            "a php prefix"     => [ "USER_ADMIN", true ],
            "a script prefix"  => [ "AUTHORIZED_ADMIN", true ],
            "nothing at all"   => [ "GENERAL_APPLY", false ],
            "a longer key"     => [ "GENERAL_ACCEPT_ALL", false ],
        ];
    }



    /**
     * The keys the source asks for by name, against what the files define
     * @param list<string> $defined
     * @param list<string> $missing
     * @return void
     */
    #[DataProvider("providerMissing")]
    public function testTheMissingKeysAreFound(array $defined, array $missing): void {
        $keys = [];
        foreach ($defined as $key) {
            $keys[$key] = true;
        }

        $found = array_keys(NLSUsage::getMissing($this->usage, $keys));
        sort($found);
        sort($missing);
        $this->assertSame($missing, $found);
    }

    /**
     * @return array<string,array{list<string>,list<string>}>
     */
    public static function providerMissing(): array {
        // Everything the source asks for, which nothing below defines in full
        $asked = [
            "GENERAL_ACCEPT", "USER_ERROR_NAME", "USER_ERROR_EMAIL",
            "USER_SUCCESS_CREATE", "GENERAL_CANCEL", "PRODUCT_COUNT",
        ];

        return [
            // A plural pair answers for the key it is written from
            "everything is defined" => [
                [ "GENERAL_ACCEPT", "USER_ERROR_NAME", "USER_ERROR_EMAIL", "USER_SUCCESS_CREATE",
                    "GENERAL_CANCEL", "GENERAL_SAVE", "PRODUCT_COUNT_SINGULAR", "PRODUCT_COUNT_PLURAL" ],
                [],
            ],
            "one is not" => [
                [ "GENERAL_ACCEPT", "USER_ERROR_NAME", "USER_SUCCESS_CREATE", "GENERAL_CANCEL",
                    "GENERAL_SAVE", "PRODUCT_COUNT_SINGULAR" ],
                [ "USER_ERROR_EMAIL" ],
            ],
            // With no key at all there is no family either, so the attributes
            // are left alone, and only what is asked for by a call is reported
            "none of them are" => [ [], $asked ],
            // An attribute holds an action of the element as well as a key, so
            // it is only taken for one when a family of its own is already there
            "an attribute of a family" => [ $asked, [ "GENERAL_SAVE" ] ],
        ];
    }

    /**
     * A reading is merged into another, the words taking the newer value and
     * the calls keeping the place the key was first seen at
     * @param array<string,mixed> $usage
     * @param array<string,mixed> $other
     * @param array<string,mixed> $expected
     * @return void
     */
    #[DataProvider("providerMerge")]
    public function testTheReadingsAreMerged(array $usage, array $other, array $expected): void {
        $this->assertSame($expected, NLSUsage::merge($usage, $other));
    }

    /**
     * @return array<string,array{array<string,mixed>,array<string,mixed>,array<string,mixed>}>
     */
    public static function providerMerge(): array {
        $empty = [ "words" => [], "prefixes" => [], "calls" => [], "props" => [] ];

        return [
            "nothing to merge"        => [ $empty, $empty, $empty ],
            "the words of both"       => [
                [ ...$empty, "words" => [ "ONE" => true ] ],
                [ ...$empty, "words" => [ "TWO" => true ] ],
                [ ...$empty, "words" => [ "ONE" => true, "TWO" => true ] ],
            ],
            "the newer word wins"     => [
                [ ...$empty, "words" => [ "ONE" => true ] ],
                [ ...$empty, "words" => [ "ONE" => false ] ],
                [ ...$empty, "words" => [ "ONE" => false ] ],
            ],
            "the prefixes of both"    => [
                [ ...$empty, "prefixes" => [ "ONE_" => true ] ],
                [ ...$empty, "prefixes" => [ "TWO_" => true ] ],
                [ ...$empty, "prefixes" => [ "ONE_" => true, "TWO_" => true ] ],
            ],
            "a call that is new"      => [
                [ ...$empty, "calls" => [ "ONE" => "a.php:1" ] ],
                [ ...$empty, "calls" => [ "TWO" => "b.php:2" ] ],
                [ ...$empty, "calls" => [ "ONE" => "a.php:1", "TWO" => "b.php:2" ] ],
            ],
            "the first place of a call" => [
                [ ...$empty, "calls" => [ "ONE" => "a.php:1" ] ],
                [ ...$empty, "calls" => [ "ONE" => "b.php:2" ] ],
                [ ...$empty, "calls" => [ "ONE" => "a.php:1" ] ],
            ],
            "the first place of a prop" => [
                [ ...$empty, "props" => [ "ONE" => "a.jsx:1" ] ],
                [ ...$empty, "props" => [ "ONE" => "b.jsx:2", "TWO" => "b.jsx:3" ] ],
                [ ...$empty, "props" => [ "ONE" => "a.jsx:1", "TWO" => "b.jsx:3" ] ],
            ],
        ];
    }
}
