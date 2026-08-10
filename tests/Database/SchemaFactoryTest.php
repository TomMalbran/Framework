<?php
namespace Tests\Database;

use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The Schema Factory, which reads the Models of the Framework and of the App
 */
class SchemaFactoryTest extends TestCase {

    /**
     * Returns the names of the given Models, in the order they come back
     * @param list<SchemaModel> $schemaModels
     * @return list<string>
     */
    private function names(array $schemaModels): array {
        $result = [];
        foreach ($schemaModels as $schemaModel) {
            $result[] = $schemaModel->name;
        }
        return $result;
    }

    /**
     * Returns a Model of the given name
     * @param string $name
     * @param bool   $fromFramework Optional.
     * @return SchemaModel
     */
    private static function model(string $name, bool $fromFramework = false): SchemaModel {
        return new SchemaModel(name: $name, fromFramework: $fromFramework);
    }



    /**
     * The Models of each side, and the names left after merging them
     * @param list<SchemaModel> $frameModels
     * @param list<SchemaModel> $appModels
     * @param list<string>      $expected
     * @return void
     */
    #[DataProvider("providerMerge")]
    public function testTheAppModelReplacesTheFrameworkOneItExtends(
        array $frameModels,
        array $appModels,
        array $expected,
    ): void {
        $result = SchemaFactory::mergeModels($frameModels, $appModels);

        $this->assertSame($expected, $this->names($result));
    }

    /**
     * A Model named the same is the same table, so only one of the two can be
     * migrated, and the Model that replaces another keeps its place
     * @return array<string,array{list<SchemaModel>,list<SchemaModel>,list<string>}>
     */
    public static function providerMerge(): array {
        return [
            "nothing at all"      => [ [], [], [] ],
            "the framework alone" => [
                [ self::model("Credential", fromFramework: true) ],
                [],
                [ "Credential" ],
            ],
            "the app alone"       => [
                [],
                [ self::model("Crate") ],
                [ "Crate" ],
            ],
            "no model in common"  => [
                [ self::model("Credential", fromFramework: true) ],
                [ self::model("Crate") ],
                [ "Credential", "Crate" ],
            ],
            "one extended"        => [
                [ self::model("Credential", fromFramework: true) ],
                [ self::model("Credential") ],
                [ "Credential" ],
            ],
            "one of each"         => [
                [
                    self::model("Credential", fromFramework: true),
                    self::model("Setting", fromFramework: true),
                ],
                [ self::model("Setting"), self::model("Crate") ],
                [ "Credential", "Setting", "Crate" ],
            ],
        ];
    }

    public function testTheModelThatIsMigratedIsTheOneOfTheApp(): void {
        // Migrating the Framework one first dropped the columns the App added
        $frameModel = $this->model("Credential", fromFramework: true);
        $appModel   = $this->model("Credential");

        $result = SchemaFactory::mergeModels([ $frameModel ], [ $appModel ]);

        $this->assertCount(1, $result);
        $this->assertSame($appModel, $result[0]);
    }

    public function testEveryModelOfTheRepositoryIsNamedOnlyOnce(): void {
        // Two Models of one name are one table, which two migrations would
        // take turns adding to and dropping from
        $names = $this->names(SchemaFactory::getData());

        $this->assertNotEmpty($names);
        $this->assertSame($names, array_values(array_unique($names)));
    }
}
