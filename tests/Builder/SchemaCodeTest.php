<?php
namespace Tests\Builder;

use Framework\Builder\Builder;
use Framework\Database\Builder\ColumnCode;
use Framework\Database\Builder\EntityCode;
use Framework\Database\Builder\QueryCode;
use Framework\Database\Builder\RequestedCode;
use Framework\Database\Builder\SchemaCode;
use Framework\Database\Builder\StatusCode;
use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionMethod;

class SchemaCodeTest extends TestCase {

    /**
     * The generators render through Mustache, and the templates are loaded by the
     * build. Loaded once here so the code that comes back is the real thing.
     * @return void
     */
    public static function setUpBeforeClass(): void {
        $method = new ReflectionMethod(Builder::class, "loadTemplates");
        $method->invoke(null);
    }

    /**
     * Returns the Framework models, keyed by name
     * @return array<string,SchemaModel>
     */
    private static function models(): array {
        static $models = null;
        if ($models === null) {
            $models = [];
            foreach (SchemaFactory::buildData(forFramework: true) as $schemaModel) {
                $models[$schemaModel->name] = $schemaModel;
            }
        }
        return $models;
    }

    /**
     * Asserts the given string is php that parses
     * @param string $code
     * @param string $where
     * @return void
     */
    private function assertParses(string $code, string $where): void {
        $this->assertNotSame("", $code, "$where generated nothing");

        $error = null;
        try {
            token_get_all($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $error = $e->getMessage();
        }
        $this->assertNull($error, "$where generated php that does not parse: $error");
    }



    #[DataProvider("providerModelNames")]
    public function testTheSchemaCodeParses(string $modelName): void {
        $schemaModel = self::models()[$modelName];
        $code        = SchemaCode::getCode($schemaModel);

        $this->assertParses($code, "$modelName schema");
        $this->assertStringContainsString("class {$schemaModel->name}Schema", $code);
        $this->assertStringContainsString("namespace {$schemaModel->namespace};", $code);
    }

    #[DataProvider("providerModelNames")]
    public function testTheEntityCodeParses(string $modelName): void {
        $schemaModel = self::models()[$modelName];
        $code        = EntityCode::getCode($schemaModel);

        $this->assertParses($code, "$modelName entity");
        $this->assertStringContainsString("class {$schemaModel->entityClass}", $code);
    }

    #[DataProvider("providerModelNames")]
    public function testTheRequestedCodeParses(string $modelName): void {
        // The generator answers for every model; it is the builder that decides
        // whether to write the file, by looking at the requested fields
        $schemaModel = self::models()[$modelName];
        $code        = RequestedCode::getCode($schemaModel);

        $this->assertParses($code, "$modelName request");
        $this->assertStringContainsString("class {$schemaModel->requestClass}", $code);
    }


    #[DataProvider("providerModelNames")]
    public function testTheQueryCodeParses(string $modelName): void {
        $schemaModel = self::models()[$modelName];
        $code        = QueryCode::getCode($schemaModel);

        $this->assertParses($code, "$modelName query");
        $this->assertStringContainsString("class {$schemaModel->queryClass}", $code);
        $this->assertStringContainsString("namespace {$schemaModel->namespace};", $code);
    }

    #[DataProvider("providerModelNames")]
    public function testTheColumnCodeParses(string $modelName): void {
        $schemaModel = self::models()[$modelName];
        $code        = ColumnCode::getCode($schemaModel);

        $this->assertParses($code, "$modelName column");
        $this->assertStringContainsString("enum {$schemaModel->columnClass}", $code);
    }

    #[DataProvider("providerModelNames")]
    public function testTheStatusCodeParses(string $modelName): void {
        // Answered for every model, the same as the request: it is the builder
        // that looks at hasStatus and decides whether to write the file
        $schemaModel = self::models()[$modelName];
        $code        = StatusCode::getCode($schemaModel);
        $whereCode   = StatusCode::getWhereCode($schemaModel);

        $this->assertParses($code, "$modelName status");
        $this->assertParses($whereCode, "$modelName status where");
        $this->assertStringContainsString("enum {$schemaModel->statusClass}", $code);
        $this->assertStringContainsString("{$schemaModel->statusClass}Where", $whereCode);
    }

    /**
     * The models are named here so a failure says which one broke
     * @return array<string,list<string>>
     */
    public static function providerModelNames(): array {
        $result = [];
        foreach (SchemaFactory::buildData(forFramework: true) as $schemaModel) {
            $result[$schemaModel->name] = [ $schemaModel->name ];
        }
        return $result;
    }


    public function testTheIdFieldReachesTheSchema(): void {
        // Credential is keyed by an auto incrementing id
        $code = SchemaCode::getCode(self::models()["Credential"]);

        $this->assertStringContainsString("CREDENTIAL_ID", $code);
    }

    public function testTheStatusReachesTheEntity(): void {
        // Credential carries a Status, which the entity has to type
        $code = EntityCode::getCode(self::models()["Credential"]);

        $this->assertStringContainsString("Status", $code);
    }

    public function testTheRequestedFieldsAreWhatTheRequestCarries(): void {
        // Credential asks for a request; Migrations does not, so its generated
        // class has the shape but none of the fields
        $withFields = RequestedCode::getCode(self::models()["Credential"]);
        $without    = RequestedCode::getCode(self::models()["Migrations"]);

        $this->assertStringContainsString("firstName", $withFields);
        $this->assertStringNotContainsString("firstName", $without);
    }
}
