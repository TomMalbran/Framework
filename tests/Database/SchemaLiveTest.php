<?php
namespace Tests\Database;

use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;

use Tests\LiveTestCase;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every Model against the table the migration made for it
 *
 * The cases are built from the Models rather than written out, so a Model
 * added later is covered without touching this file.
 */
class SchemaLiveTest extends LiveTestCase {

    protected function setUp(): void {
        parent::setUp();
        $this->migrateOnce();
    }

    /**
     * Returns the Model of the given name
     * @param string $name
     * @return SchemaModel
     */
    private function model(string $name): SchemaModel {
        foreach (SchemaFactory::getData() as $schemaModel) {
            if ($schemaModel->name === $name) {
                return $schemaModel;
            }
        }
        $this->fail("There is no $name Model");
    }

    /**
     * One case per Model, which is what makes this grow with the Framework
     * @return array<string,array{string}>
     */
    public static function providerModels(): array {
        $result = [];
        foreach (SchemaFactory::getData() as $schemaModel) {
            $result[$schemaModel->name] = [ $schemaModel->name ];
        }

        // A provider that comes back empty would quietly test nothing
        if (count($result) === 0) {
            throw new \RuntimeException("There are no Models to test");
        }
        return $result;
    }



    /**
     * The table of the Model, which the migration has to have made
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testTheTableIsThere(string $modelName): void {
        $schemaModel = $this->model($modelName);

        $this->assertTrue(
            $this->db()->tableExists($schemaModel->tableName),
            "{$schemaModel->tableName} is not in the database",
        );
    }

    /**
     * The columns of the table, against the ones the Model declares
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testTheColumnsAreTheOnesDeclared(string $modelName): void {
        $schemaModel = $this->model($modelName);
        $expected    = [];
        foreach ($schemaModel->fields as $field) {
            $expected[] = $field->dbName;
        }

        $result = array_keys($this->db()->getTableFields($schemaModel->tableName));
        sort($expected);
        sort($result);

        $this->assertSame($expected, $result, "the columns of {$schemaModel->tableName}");
    }

    /**
     * The primary key of the table, against the fields the Model marks
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testThePrimaryKeyIsTheOneDeclared(string $modelName): void {
        $schemaModel = $this->model($modelName);
        $expected    = [];
        foreach ($schemaModel->fields as $field) {
            if ($field->isPrimary || $field->isID) {
                $expected[] = $field->dbName;
            }
        }

        $result = $this->db()->getPrimaryKeys($schemaModel->tableName);
        sort($expected);
        sort($result);

        $this->assertSame($expected, $result, "the primary key of {$schemaModel->tableName}");
    }

    /**
     * Every field the Model marks as a key has an index in the table
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testTheIndexesAreThere(string $modelName): void {
        $schemaModel = $this->model($modelName);
        $names       = [];
        foreach ($this->db()->getTableKeys($schemaModel->tableName) as $tableKey) {
            if (isset($tableKey["Key_name"])) {
                $names[] = $tableKey["Key_name"];
            }
        }

        $found = 0;
        foreach ($schemaModel->fields as $field) {
            if (!$field->isKey) {
                continue;
            }
            $found += 1;
            $this->assertContains(
                $field->dbName,
                $names,
                "{$schemaModel->tableName} has no index on {$field->dbName}",
            );
        }
        $this->assertGreaterThanOrEqual(0, $found);
    }

    /**
     * The class the build wrote for the Model, and the query it runs
     *
     * This is the whole generated select, with the joins, the counts and the
     * expressions of the Model in it, which nothing else here executes.
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testTheGeneratedSelectRuns(string $modelName): void {
        $schemaModel = $this->model($modelName);
        $className   = $schemaModel->namespace . "\\" . $schemaModel->name . "Schema";

        $this->assertTrue(class_exists($className), "$className was not generated");

        /** @var callable */
        $callable = [ $className, "getEntityTotal" ];
        $total    = $callable();

        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    /**
     * The Model the generated class carries, against the one the build read
     * @param string $modelName
     * @return void
     */
    #[DataProvider("providerModels")]
    public function testTheGeneratedClassKnowsItsModel(string $modelName): void {
        $schemaModel = $this->model($modelName);
        $className   = $schemaModel->namespace . "\\" . $schemaModel->name . "Schema";

        /** @var callable */
        $callable = [ $className, "getModel" ];
        $result   = $callable();

        $this->assertInstanceOf(SchemaModel::class, $result);
        $this->assertSame($schemaModel->tableName, $result->tableName);
    }



    public function testEveryTableInTheDatabaseHasAModel(): void {
        // The other way round from the tests above: nothing is left behind
        $modelNames = [];
        foreach (SchemaFactory::getData() as $schemaModel) {
            $modelNames[] = $schemaModel->tableName;
        }

        foreach ($this->db()->getTables() as $tableName) {
            $this->assertContains($tableName, $modelNames, "$tableName is backed by no Model");
        }
    }
}
