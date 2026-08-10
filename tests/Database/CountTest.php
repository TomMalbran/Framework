<?php
namespace Tests\Database;

use Framework\Database\Model\Count;
use Framework\Database\SchemaModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Count Attribute, which counts the rows of another Model in a subquery
 */
class CountTest extends TestCase {

    /**
     * Returns the SQL without the padding the builder leaves between clauses
     * @param Count  $count
     * @param string $mainKey Optional.
     * @return string
     */
    private function sql(Count $count, string $mainKey = "crates"): string {
        $result = preg_replace('/\s+/', " ", $count->getExpression($mainKey)->toSQL());
        return trim((string)$result);
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
        $count     = new Count(modelName: $modelName, otherModelName: $modelName);

        $this->assertSame($expected, $count->modelName);
        $this->assertSame($expected, $count->otherModelName);
    }

    /**
     * @return array<string,array{string|null,string}>
     */
    public static function providerModelName(): array {
        return [
            "nothing given"    => [ null, "" ],
            "a plain name"     => [ "Crate", "Crate" ],
            "with the suffix"  => [ "CrateModel", "Crate" ],
            "with a namespace" => [ "App\\Schema\\CrateModel", "Crate" ],
            "nothing at all"   => [ "", "" ],
        ];
    }

    public function testTheAttributeStartsWithNothingSet(): void {
        $count = new Count();

        $this->assertSame("", $count->modelName);
        $this->assertSame("", $count->otherModelName);
        $this->assertSame("", $count->fieldName);
        $this->assertSame("", $count->query);
        $this->assertSame("", $count->name);
        $this->assertFalse($count->hasDeleted);
    }

    public function testTheAttributeKeepsTheFieldAndTheQuery(): void {
        $count = new Count(fieldName: "crateID", query: "isActive = 1");

        $this->assertSame("crateID", $count->fieldName);
        $this->assertSame("isActive = 1", $count->query);
    }



    public function testACountIsCreatedWithEveryValue(): void {
        $count = Count::create("total", "Item", "Box", "crateID", "isActive = 1", hasDeleted: true);

        $this->assertSame("total", $count->name);
        $this->assertSame("Item", $count->modelName);
        $this->assertSame("Box", $count->otherModelName);
        $this->assertSame("crateID", $count->fieldName);
        $this->assertSame("isActive = 1", $count->query);
        $this->assertTrue($count->hasDeleted);
    }

    public function testTheNameComesFromTheProperty(): void {
        $count = new Count(modelName: "Item");

        $this->assertSame($count, $count->setData("itemsCount"));
        $this->assertSame("itemsCount", $count->name);
    }

    /**
     * The field the join is made on, given what the attribute named and the
     * ID of the Model the property was declared in
     * @param string $given
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerFieldName")]
    public function testTheFieldFallsBackToTheIdOfTheParent(string $given, string $expected): void {
        $parent = new SchemaModel(name: "Crate");
        $parent->idName = "crateID";

        $count = new Count(modelName: "Item", fieldName: $given);
        $count->setModel(new SchemaModel(name: "Item"), $parent);

        $this->assertSame($expected, $count->fieldName);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function providerFieldName(): array {
        return [
            "nothing given" => [ "", "crateID" ],
            "a field"       => [ "boxID", "boxID" ],
        ];
    }

    /**
     * Whether the counted Model can be deleted, which the Count has to skip
     * @param bool $canDelete
     * @return void
     */
    #[DataProvider("providerCanDelete")]
    public function testTheDeletedRowsAreSkippedWhenTheModelHasThem(bool $canDelete): void {
        $count = new Count(modelName: "Item");
        $count->setModel(new SchemaModel(name: "Item", canDelete: $canDelete), new SchemaModel());

        $this->assertSame($canDelete, $count->hasDeleted);
        $this->assertSame($canDelete, str_contains($this->sql($count), "isDeleted = 0"));
    }

    /**
     * @return array<string,array{bool}>
     */
    public static function providerCanDelete(): array {
        return [
            "it can be deleted" => [ true ],
            "it cannot"         => [ false ],
        ];
    }



    /**
     * A Count, and the subquery it becomes
     * @param Count  $count
     * @param string $expected
     * @return void
     */
    #[DataProvider("providerExpression")]
    public function testTheCountIsASubqueryOverTheOtherTable(Count $count, string $expected): void {
        $this->assertSame($expected, $this->sql($count));
    }

    /**
     * The counted table is aliased, so that a Model counting its own rows
     * does not end up joining a table to itself
     * @return array<string,array{Count,string}>
     */
    public static function providerExpression(): array {
        $base = "SELECT COUNT(*) FROM `crate_item` AS `crate_item_count` WHERE";
        return [
            "the main table"   => [
                Count::create("total", "CrateItem", "", "crateID", "", hasDeleted: false),
                "$base crate_item_count.CRATE_ID = crates.CRATE_ID",
            ],
            "another table"    => [
                Count::create("total", "CrateItem", "Box", "crateID", "", hasDeleted: false),
                "$base crate_item_count.CRATE_ID = box.CRATE_ID",
            ],
            "an extra query"   => [
                Count::create("total", "CrateItem", "", "crateID", "isActive = 1", hasDeleted: false),
                "$base crate_item_count.CRATE_ID = crates.CRATE_ID AND isActive = 1",
            ],
            "the deleted rows" => [
                Count::create("total", "CrateItem", "", "crateID", "", hasDeleted: true),
                "$base crate_item_count.CRATE_ID = crates.CRATE_ID AND crate_item_count.isDeleted = 0",
            ],
            "a plain field"    => [
                Count::create("total", "CrateItem", "", "position", "", hasDeleted: false),
                "$base crate_item_count.position = crates.position",
            ],
        ];
    }



    /**
     * The row the Count was read into, and the number taken from it
     * @param array<string,mixed> $data
     * @param mixed               $expected
     * @return void
     */
    #[DataProvider("providerValue")]
    public function testTheValueIsTakenFromTheRowByName(array $data, mixed $expected): void {
        $count = Count::create("itemsCount", "Item", "", "crateID", "", hasDeleted: false);

        $this->assertSame($expected, $count->getValue($data));
    }

    /**
     * @return array<string,array{array<string,mixed>,mixed}>
     */
    public static function providerValue(): array {
        return [
            "a number"       => [ [ "itemsCount" => 3 ], 3 ],
            "a zero"         => [ [ "itemsCount" => 0 ], 0 ],
            "another column" => [ [ "otherCount" => 3 ], 0 ],
            "nothing at all" => [ [], 0 ],
        ];
    }

    public function testTheBuildDataNamesEveryValue(): void {
        $count = Count::create("total", "Item", "Box", "crateID", "isActive = 1", hasDeleted: true);

        $this->assertSame([
            "name"           => "total",
            "modelName"      => "Item",
            "otherModelName" => "Box",
            "fieldName"      => "crateID",
            "query"          => "isActive = 1",
            "hasDeleted"     => true,
        ], $count->toBuildData());
    }
}
