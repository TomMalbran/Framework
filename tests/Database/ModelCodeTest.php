<?php
namespace Tests\Database;

use Framework\Application;
use Framework\Builder\Builder;
use Framework\Database\SchemaBuilder;
use Framework\Database\Builder\MediaCode;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\File\Storage;

use Tests\TestHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use ParseError;

/**
 * The code written for a Model that has one field of every kind
 *
 * The Models of the Framework are the ones the Framework needs, which leaves
 * the halves of the generators that write floats, files, dates with a period,
 * lists, counts, relations and states with nothing to write. The Models under
 * the fixtures are pointed at instead, and what comes out is read back rather
 * than run: none of this needs a database.
 */
class ModelCodeTest extends TestCase {
    use TestHelpers;

    private const SourceDir  = "tests/Database/Every";
    private const Namespace  = "Tests\\Database\\Every\\";
    private const FixtureDir = "tests/Database/.tmp_every";

    /** @var list<SchemaModel> */
    private static array $schemaModels = [];

    private static bool $isBuilt = false;


    protected function setUp(): void {
        $this->build();
    }

    public static function tearDownAfterClass(): void {
        Storage::deleteDir(Application::getBasePath(self::FixtureDir));
    }

    /**
     * Writes the classes of the Models of the fixtures, once for the class
     *
     * The Application is what the discovery of an App reads, so it is pointed
     * at the fixtures for as long as this takes and put back after
     * @return void
     */
    private function build(): void {
        if (self::$isBuilt) {
            return;
        }
        self::$isBuilt = true;

        // The code is rendered from templates the build reads once, so
        // without them every file would be written empty
        $this->callPrivateStaticMethod(Builder::class, "loadTemplates");

        $sourceDir = $this->getPrivateStaticProperty(Application::class, "sourceDir");
        $namespace = $this->getPrivateStaticProperty(Application::class, "namespace");
        $this->setPrivateStaticProperty(Application::class, "loaded", true);
        $this->setPrivateStaticProperty(Application::class, "sourceDir", self::SourceDir);
        $this->setPrivateStaticProperty(Application::class, "namespace", self::Namespace);

        try {
            $schemaModels       = SchemaFactory::buildData();
            self::$schemaModels = $schemaModels;
            foreach ($schemaModels as $schemaModel) {
                $schemaModel->path = Application::getBasePath(self::FixtureDir, $schemaModel->name);
            }

            ob_start();
            try {
                SchemaBuilder::generateSchemaCode($schemaModels, "Every");
            } finally {
                ob_get_clean();
            }
        } finally {
            $this->setPrivateStaticProperty(Application::class, "sourceDir", $sourceDir);
            $this->setPrivateStaticProperty(Application::class, "namespace", $namespace);
        }
    }

    /**
     * Returns the code written for the given class of the given Model
     * @param string $model
     * @param string $class
     * @return string
     */
    private function codeOf(string $model, string $class): string {
        $path = Application::getBasePath(self::FixtureDir, $model);
        return Storage::readFile($path, "{$model}{$class}.php");
    }



    public function testTheClassesAreWritten(): void {
        $path  = Application::getBasePath(self::FixtureDir, "EveryField");
        $files = Storage::getFilesInDir($path);

        $this->assertContains("EveryFieldSchema.php", $files);
        $this->assertContains("EveryFieldEntity.php", $files);
        $this->assertContains("EveryFieldColumn.php", $files);
        $this->assertContains("EveryFieldQuery.php", $files);
        $this->assertContains("EveryFieldRequest.php", $files);
    }

    public function testEveryClassIsValidPhp(): void {
        // Nothing runs the generated code here, so this is what says the
        // templates were filled with something that parses
        $path = Application::getBasePath(self::FixtureDir);
        foreach ([ "EveryField", "EveryTag", "EveryCode", "EveryLink" ] as $model) {
            foreach (Storage::getFilesInDir("$path/$model") as $file) {
                $code = Storage::readFile("$path/$model", $file);
                $this->assertNotEmpty($code, "$model/$file is empty");
                $this->assertNull(
                    self::syntaxErrorOf($code),
                    "$model/$file does not parse: " . self::syntaxErrorOf($code),
                );
            }
        }
    }

    /**
     * Returns the message of the syntax error of the given code, or null
     * @param string $code
     * @return string|null
     */
    private static function syntaxErrorOf(string $code): ?string {
        try {
            token_get_all($code, TOKEN_PARSE);
            return null;
        } catch (ParseError $e) {
            return $e->getMessage();
        }
    }



    public function testAFloatIsScaledToAnInt(): void {
        // A float is kept as an int of its decimals, so every write of one
        // has to say how many it has
        $code = $this->codeOf("EveryField", "Schema");

        $this->assertStringContainsString('$fields["weight"] = Numbers::toInt($weight, 3);', $code);
        $this->assertStringContainsString('$fields["price"] = Numbers::toInt($price, 2);', $code);
    }

    public function testAFloatIsLeftAloneOnAnEdit(): void {
        // An edit can be given an expression instead of a value, and one is
        // not a number to scale
        $this->assertStringContainsString(
            '$fields["price"] = $price instanceof Assign ? $price : Numbers::toInt($price, 2);',
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testAFileGetsItsUrl(): void {
        $this->assertStringContainsString(
            'case PictureUrl',
            $this->codeOf("EveryField", "Column"),
        );
    }

    public function testAFileIsAFileInTheEntity(): void {
        $code = $this->codeOf("EveryField", "Entity");

        $this->assertStringContainsString("public File \$picture;", $code);
        $this->assertStringContainsString("public string \$pictureUrl = \"\";", $code);
        $this->assertStringContainsString("public string \$pictureThumb = \"\";", $code);
        $this->assertStringContainsString("use Framework\\File\\File;", $code);
    }

    public function testAFileIsNeverNull(): void {
        $this->assertStringContainsString(
            '$this->picture = $picture ?? new File();',
            $this->codeOf("EveryField", "Entity"),
        );
    }



    public function testTheStatesAreTheStatus(): void {
        $code = $this->codeOf("EveryTag", "Status");

        $this->assertStringContainsString("case Draft;", $code);
        $this->assertStringContainsString("case Published;", $code);
        $this->assertStringContainsString("case Archived;", $code);
    }

    public function testEachStateKeepsItsColor(): void {
        $code = $this->codeOf("EveryTag", "Status");

        $this->assertStringContainsString('self::Draft => "yellow",', $code);
        $this->assertStringContainsString('self::Published => "green",', $code);
        $this->assertStringContainsString('self::Archived => "red",', $code);
    }

    public function testAHiddenStateIsNotOffered(): void {
        // It is still a Status, it is just not one to choose
        $code = $this->codeOf("EveryTag", "Status");

        $this->assertStringContainsString("[ self::Draft, self::Published ]", $code);
        $this->assertStringNotContainsString("self::Archived ]", $code);
    }

    public function testWithNoStatesTheDefaultsAreUsed(): void {
        $code = $this->codeOf("EveryField", "Status");

        $this->assertStringContainsString("case Active;", $code);
        $this->assertStringContainsString("case Inactive;", $code);
    }



    public function testEachRuleIsWritten(): void {
        $code = $this->codeOf("EveryField", "Schema");

        $this->assertStringContainsString("Strings::length(\$request->name) > 40", $code);
        $this->assertStringContainsString("Utils::isValidEmail(\$request->email)", $code);
        $this->assertStringContainsString("URL::isValid(\$request->website)", $code);
        $this->assertStringContainsString("EveryKind::isValid(\$request->kind)", $code);
    }

    public function testTheDatesAreAPeriod(): void {
        // The second of two dates is checked against the first, which is what
        // a from and a to are for
        $this->assertStringContainsString(
            '$request->fromTime->isValidPeriod($request->toTime)',
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testADateChecksItsHour(): void {
        $this->assertStringContainsString(
            '$request->fromTime->hasHour() && !$request->fromTime->isValidHour()',
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testAListChecksWhatItHolds(): void {
        $this->assertStringContainsString(
            'EveryTagModel::exists($listID)',
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testAPriceIsChecked(): void {
        $this->assertStringContainsString(
            "Numbers::isValidPrice(\$request->price, 0)",
            $this->codeOf("EveryField", "Schema"),
        );
    }



    public function testTheModelIsDescribed(): void {
        $code = $this->codeOf("EveryField", "Schema");

        $this->assertStringContainsString('name: "weight", type: FieldType::Float, decimals: 3', $code);
        $this->assertStringContainsString('name: "picture", type: FieldType::File', $code);
        $this->assertStringContainsString('name: "tagIDs", type: FieldType::JSON', $code);
        $this->assertStringContainsString('name: "kind", type: FieldType::Enum', $code);
    }

    public function testACountReachesTheEntity(): void {
        $this->assertStringContainsString(
            "public int \$tagCount = 0;",
            $this->codeOf("EveryField", "Entity"),
        );
    }

    public function testARelationReachesTheEntity(): void {
        $this->assertStringContainsString(
            "public string \$everyTagName = \"\";",
            $this->codeOf("EveryField", "Entity"),
        );
    }

    public function testTheStatusOfARelation(): void {
        // The Model it joins has one of its own, so what is joined is that
        // Status rather than the string the column holds
        $code = $this->codeOf("EveryField", "Entity");

        $this->assertStringContainsString(
            "public EveryTagStatus \$everyTagStatus = EveryTagStatus::None;",
            $code,
        );
        $this->assertStringContainsString(
            "use Tests\\Database\\Every\\Schema\\EveryTagStatus;",
            $code,
        );
    }

    public function testASubRequestIsItsEntities(): void {
        $code = $this->codeOf("EveryField", "Entity");

        $this->assertStringContainsString("public array \$tags = [];", $code);
        $this->assertStringContainsString("\$this->tags[] = new EveryTagEntity(\$subData);", $code);
    }

    public function testASubRequestOfNoModel(): void {
        // One of a type of its own is kept as it is written, and has no
        // Schema to import: one was written before, and it was a clash with
        // the Schema of the Framework that would not even parse
        $this->assertStringContainsString(
            "@param list<EveryKind>  \$kindList",
            $this->codeOf("EveryField", "Entity"),
        );
        $this->assertStringNotContainsString(
            "use \\Schema;",
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testTheStatusIsChecked(): void {
        $this->assertStringContainsString(
            "EveryFieldStatus::isValid(\$request->status)",
            $this->codeOf("EveryField", "Schema"),
        );
    }

    public function testAnExpressionIsAColumn(): void {
        $this->assertStringContainsString(
            "case TagTotal",
            $this->codeOf("EveryField", "Column"),
        );
    }

    public function testAVirtualIsAnArray(): void {
        $this->assertStringContainsString(
            "public array \$tagNames = [];",
            $this->codeOf("EveryField", "Entity"),
        );
    }

    public function testAVirtualIsOfItsOwnType(): void {
        $code = $this->codeOf("EveryField", "Entity");

        $this->assertStringContainsString("public Date \$seenTime;", $code);
        $this->assertStringContainsString("@param list<EveryKind>  \$seenKinds", $code);
    }



    public function testAFileIsReadFromTheRequest(): void {
        $this->assertStringContainsString(
            "getFile(\"upload\")",
            $this->codeOf("EveryField", "Request"),
        );
    }

    public function testAListOfEnumsIsRead(): void {
        $this->assertStringContainsString(
            "EveryKind::fromList(",
            $this->codeOf("EveryField", "Request"),
        );
    }

    /**
     * Each shape of map is read with the function that makes it
     * @param string $name
     * @param string $function
     * @return void
     */
    #[DataProvider("providerMaps")]
    public function testAMapIsReadAsItsType(string $name, string $function): void {
        $this->assertStringContainsString(
            "\$instance->{$name} = Arrays::{$function}(\$instance->request->getJSONArray(\"{$name}\"))",
            $this->codeOf("EveryField", "Request"),
        );
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerMaps(): array {
        return [
            "of strings"      => [ "labels", "toStringsMap" ],
            "of ints"         => [ "sizes",  "toIntsMap" ],
            "int to string"   => [ "titles", "toIntStringMap" ],
            "string to int"   => [ "counts", "toStringIntMap" ],
            "string to float" => [ "rates",  "toStringFloatMap" ],
            "string to mixed" => [ "extras", "toStringMixedMap" ],
        ];
    }

    public function testAnEnumIsTheID(): void {
        // The rows are the cases of the Enum, so the ID is read as one
        $code = $this->codeOf("EveryCode", "Request");

        $this->assertStringContainsString("public EveryKind \$code = EveryKind::None;", $code);
        $this->assertStringContainsString("use Tests\\Database\\Every\\EveryKind;", $code);
    }

    public function testAMapOfNoShapeIsLeftOut(): void {
        // The request has no function that makes one, so it is not read
        $this->assertStringNotContainsString(
            "\$instance->ratios =",
            $this->codeOf("EveryField", "Request"),
        );
    }

    public function testAFieldStandsInForTheID(): void {
        // The Model has no ID of its own, so the field that says it is the
        // one is what the request is keyed by
        $this->assertStringContainsString(
            "public string \$linkCode;",
            $this->codeOf("EveryLink", "Request"),
        );
    }


    /**
     * A field that holds a file, and how the Media build says to write it
     * @param string $fieldName
     * @param string $key
     * @return void
     */
    #[DataProvider("providerMediaFields")]
    public function testAFileFieldIsListed(string $fieldName, string $key): void {
        $fields = MediaCode::getFields(self::$schemaModels);

        $row = null;
        foreach ($fields as $field) {
            if ($field["fieldName"] === $fieldName) {
                $row = $field;
            }
        }

        $this->assertNotNull($row, "$fieldName is not listed");
        $this->assertTrue($row[$key], "$fieldName is not $key");
        $this->assertSame("every_field", $row["tableName"]);
    }

    /**
     * A file is the whole value of a plain column, is named somewhere inside
     * a text, or is one of the paths a JSON column holds
     * @return array<string,array{string,string}>
     */
    public static function providerMediaFields(): array {
        return [
            "a column of its own" => [ "picture", "isSet" ],
            "named inside a text" => [ "notes",   "isReplace" ],
            "one of a list"       => [ "files",   "isJSON" ],
        ];
    }

    public function testTheMediaCodeIsWritten(): void {
        // It is written from every Model there is, so this writes the one of
        // the fixtures and puts the one of the repository back after
        $sourceDir = $this->getPrivateStaticProperty(Application::class, "sourceDir");
        $namespace = $this->getPrivateStaticProperty(Application::class, "namespace");
        $this->setPrivateStaticProperty(Application::class, "sourceDir", self::SourceDir);
        $this->setPrivateStaticProperty(Application::class, "namespace", self::Namespace);

        ob_start();
        try {
            $written = MediaCode::generateCode();
        } finally {
            ob_get_clean();
            $this->setPrivateStaticProperty(Application::class, "sourceDir", $sourceDir);
            $this->setPrivateStaticProperty(Application::class, "namespace", $namespace);

            ob_start();
            MediaCode::generateCode();
            ob_get_clean();
        }

        $this->assertSame(1, $written);
    }
}
