<?php
namespace Tests\Database;

use Framework\Database\Model\SubRequest;
use Framework\Database\Query\Query;
use Framework\Database\SchemaModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use ReflectionMethod;

/**
 * The SubRequest Attribute, which reads the rows of another Model in one query
 *
 * Only the query it builds is covered here. Running it goes through the
 * SelectionBuilder to the database, which a test run has none of.
 */
class SubRequestTest extends TestCase {

    /**
     * Returns the SQL of the query built for the given rows
     * @param SubRequest                $subRequest
     * @param list<array<string,mixed>> $result
     * @param string                    $tableName  Optional.
     * @return string
     */
    private function sql(SubRequest $subRequest, array $result, string $tableName = "items"): string {
        $method = new ReflectionMethod($subRequest, "createQuery");
        $query  = $method->invoke($subRequest, $result, $tableName);

        $this->assertInstanceOf(Query::class, $query);
        $sql = preg_replace('/\s+/', " ", $query->toSQL());
        return trim((string)$sql);
    }

    /**
     * Returns a SubRequest already given the Model it reads
     * @param bool   $canDelete    Optional.
     * @param bool   $hasPositions Optional.
     * @param string $query        Optional.
     * @param string $orderBy      Optional.
     * @param bool   $orderAsc     Optional.
     * @return SubRequest
     */
    private static function overModel(
        bool $canDelete = false,
        bool $hasPositions = false,
        string $query = "",
        string $orderBy = "",
        bool $orderAsc = true,
    ): SubRequest {
        $schemaModel = new SchemaModel(name: "Item", canDelete: $canDelete);
        if ($hasPositions) {
            $schemaModel->hasPositions = true;
            $schemaModel->positionName = "position";
        }

        return SubRequest::create(
            schemaModel: $schemaModel,
            name:        "items",
            idName:      "crateID",
            idDbName:    "CRATE_ID",
            fieldName:   "",
            valueName:   "",
            query:       $query,
            orderBy:     $orderBy,
            orderAsc:    $orderAsc,
        );
    }



    /**
     * A Model name given to the attribute, and the name it is stored under
     * @param string|null $given
     * @param string      $expected
     * @return void
     */
    #[DataProvider("providerModelName")]
    public function testTheModelNameIsStrippedOfItsNamespaceAndSuffix(
        ?string $given,
        string $expected,
    ): void {
        /** @var class-string|null */
        $modelName = $given;

        $this->assertSame($expected, (new SubRequest(modelName: $modelName))->modelName);
    }

    /**
     * @return array<string,array{string|null,string}>
     */
    public static function providerModelName(): array {
        return [
            "nothing given"    => [ null, "" ],
            "a plain name"     => [ "Item", "Item" ],
            "with the suffix"  => [ "ItemModel", "Item" ],
            "with a namespace" => [ "App\\Schema\\ItemModel", "Item" ],
        ];
    }

    public function testTheAttributeKeepsEveryValueItWasGiven(): void {
        $subRequest = new SubRequest(
            modelName: "ItemModel",
            idName:    "crateID",
            fieldName: "kind",
            valueName: "total",
            query:     "isActive = 1",
            orderBy:   "position",
            orderAsc:  false,
        );

        $this->assertSame("crateID", $subRequest->idName);
        $this->assertSame("kind", $subRequest->fieldName);
        $this->assertSame("total", $subRequest->valueName);
        $this->assertSame("isActive = 1", $subRequest->query);
        $this->assertSame("position", $subRequest->orderBy);
        $this->assertFalse($subRequest->orderAsc);
    }

    public function testASubRequestIsCreatedWithTheModelItReads(): void {
        $schemaModel = new SchemaModel(name: "Item");
        $subRequest  = SubRequest::create(
            schemaModel: $schemaModel,
            name:        "items",
            idName:      "crateID",
            idDbName:    "CRATE_ID",
            fieldName:   "kind",
            valueName:   "total",
            query:       "isActive = 1",
        );

        $this->assertSame($schemaModel, $subRequest->schemaModel);
        $this->assertSame("items", $subRequest->name);
        $this->assertSame("CRATE_ID", $subRequest->idDbName);
        $this->assertSame("crateID", $subRequest->idName);
        $this->assertTrue($subRequest->orderAsc);
    }

    /**
     * What the property declared, and the type the SubRequest reads it as
     * @param string $modelName
     * @param string $type
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerDocType")]
    public function testTheTypeIsWrittenFromWhatThePropertyDeclared(
        string $modelName,
        string $type,
        string $expected,
    ): void {
        $subRequest = (new SubRequest())->setData("items", $modelName, $type);

        $this->assertSame("items", $subRequest->name);
        $this->assertSame($expected, $subRequest->getDocType());
    }

    /**
     * A property naming a Model is a list of its Entities, and one naming a
     * Model with a key type is a map of them
     * @return array<string,array{string,string,string}>
     */
    public static function providerDocType(): array {
        return [
            "nothing given"  => [ "", "", "" ],
            "a type alone"   => [ "", "list<string>", "list<string>" ],
            "a model"        => [ "Item", "", "list<ItemEntity>" ],
            "a model by key" => [ "Item", "string", "array<string,ItemEntity>" ],
        ];
    }

    public function testTheModelIsNamedWithItsSchemaAndNamespace(): void {
        $schemaModel = new SchemaModel(name: "Item", namespace: "App\\Schema");
        $parentModel = new SchemaModel(name: "Crate");

        $subRequest = (new SubRequest())->setModel($schemaModel, $parentModel);

        $this->assertSame($schemaModel, $subRequest->schemaModel);
        $this->assertSame($parentModel, $subRequest->parentModel);
        $this->assertSame("App\\Schema", $subRequest->namespace);
        $this->assertSame("ItemSchema", $subRequest->className);
    }

    /**
     * A Model name, and the table the rows are read from
     * @param string $modelName
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerTableName")]
    public function testTheTableIsNamedInSnakeCase(string $modelName, string $expected): void {
        $subRequest = (new SubRequest())->setData("items", $modelName, "");

        $this->assertSame($expected, $subRequest->getDbTableName());
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerTableName(): array {
        return [
            "one word"  => [ "Item", "item" ],
            "two words" => [ "CrateItem", "crate_item" ],
            "nothing"   => [ "", "" ],
        ];
    }



    /**
     * A set of rows with no id in them, which leaves nothing to ask for
     * @param list<array<string,mixed>> $result
     * @return void
     */
    #[DataProvider("providerNoIds")]
    public function testNoIdsMeanNoQueryAndNoRows(array $result): void {
        $subRequest = self::overModel();
        $method     = new ReflectionMethod($subRequest, "createQuery");

        $this->assertNull($method->invoke($subRequest, $result, "items"));
        $this->assertSame([], $subRequest->request($result));
    }

    /**
     * An id is only taken when it is a number or a string, since that is
     * what the IN of the query can be given
     * @return array<string,array{list<array<string,mixed>>}>
     */
    public static function providerNoIds(): array {
        return [
            "no rows"           => [ [] ],
            "a row with no id"  => [ [ [ "name" => "Crate" ] ] ],
            "an id that is not" => [ [ [ "crateID" => [ 1, 2 ] ] ] ],
        ];
    }

    public function testAModelThatWasNeverSetReadsNothing(): void {
        $subRequest = new SubRequest(modelName: "Item", idName: "crateID");

        $this->assertSame([], $subRequest->request([ [ "crateID" => 1 ] ]));
    }

    /**
     * A SubRequest, and the query it reads its rows with
     * @param SubRequest $subRequest
     * @param string     $expected
     * @return void
     */
    #[DataProvider("providerQuery")]
    public function testTheRowsAreReadWithOneQueryOverEveryId(
        SubRequest $subRequest,
        string $expected,
    ): void {
        $rows = [ [ "crateID" => 1 ], [ "crateID" => 2 ], [ "name" => "Crate" ] ];

        $this->assertSame($expected, $this->sql($subRequest, $rows));
    }

    /**
     * The ids of every row are asked for at once, which is the whole point of
     * the SubRequest, and the extra query is read three words at a time. One
     * that does not divide into threes is dropped whole, rather than leaving
     * the conditions it does hold
     * @return array<string,array{SubRequest,string}>
     */
    public static function providerQuery(): array {
        $base = "SELECT * FROM `items` WHERE CRATE_ID IN (?,?)";

        return [
            "the ids alone"            => [
                self::overModel(),
                $base,
            ],
            "the deleted rows"         => [
                self::overModel(canDelete: true),
                "$base AND item.isDeleted = ?",
            ],
            "an extra query"           => [
                self::overModel(query: "isActive = 1"),
                "$base AND isActive = ?",
            ],
            "two extra ones"           => [
                self::overModel(query: "isActive = 1 kind <> box"),
                "$base AND isActive = ? AND kind <> ?",
            ],
            "half a query"             => [
                self::overModel(query: "isActive ="),
                $base,
            ],
            "one and a half"           => [
                self::overModel(query: "isActive = 1 kind"),
                $base,
            ],
            "a sort"                   => [
                self::overModel(orderBy: "name"),
                "$base ORDER BY name ASC",
            ],
            "a sort the other way"     => [
                self::overModel(orderBy: "name", orderAsc: false),
                "$base ORDER BY name DESC",
            ],
            "the position"             => [
                self::overModel(hasPositions: true),
                "$base ORDER BY item.position ASC",
            ],
            "a sort over the position" => [
                self::overModel(hasPositions: true, orderBy: "name"),
                "$base ORDER BY name ASC",
            ],
        ];
    }

    /**
     * A row, and the value the SubRequest keeps from it
     * @param string              $valueName
     * @param array<string,mixed> $row
     * @param mixed               $expected
     * @return void
     */
    #[DataProvider("providerValues")]
    public function testOnlyTheNamedValueIsKeptFromTheRow(
        string $valueName,
        array $row,
        mixed $expected,
    ): void {
        $subRequest = new SubRequest(valueName: $valueName);
        $method     = new ReflectionMethod($subRequest, "getValues");

        $this->assertSame($expected, $method->invoke($subRequest, $row));
    }

    /**
     * With no value named the whole row is kept, and so is a row that does
     * not hold the one that was named
     * @return array<string,array{string,array<string,mixed>,mixed}>
     */
    public static function providerValues(): array {
        return [
            "nothing named" => [ "", [ "name" => "Crate" ], [ "name" => "Crate" ] ],
            "a value"       => [ "name", [ "name" => "Crate" ], "Crate" ],
            "one it lacks"  => [ "total", [ "name" => "Crate" ], [ "name" => "Crate" ] ],
        ];
    }



    public function testTheBuildDataNamesTheIdTheAttributeGave(): void {
        $subRequest = (new SubRequest(idName: "crateID", fieldName: "kind", valueName: "total"))
            ->setData("items", "Item", "")
            ->setModel(new SchemaModel(name: "Item"), new SchemaModel(name: "Crate"));

        $this->assertSame([
            "schemaModel" => "ItemSchema",
            "name"        => "items",
            "idName"      => "crateID",
            "idDbName"    => "CRATE_ID",
            "fieldName"   => "kind",
            "valueName"   => "total",
            "query"       => "",
            "orderBy"     => "",
            "orderAsc"    => true,
        ], $subRequest->toBuildData());
    }

    public function testTheBuildDataFallsBackToTheIdOfTheParent(): void {
        $parentModel = new SchemaModel(name: "Crate");
        $parentModel->idName   = "crateID";
        $parentModel->idDbName = "CRATE_ID";

        $subRequest = (new SubRequest())
            ->setData("items", "Item", "")
            ->setModel(new SchemaModel(name: "Item"), $parentModel);

        $result = $subRequest->toBuildData();
        $this->assertSame("crateID", $result["idName"]);
        $this->assertSame("CRATE_ID", $result["idDbName"]);
    }

    public function testTheBuildDataNamesNoIdWhenThereIsNone(): void {
        $result = (new SubRequest())->toBuildData();

        $this->assertSame("", $result["idName"]);
        $this->assertSame("", $result["idDbName"]);
    }
}
