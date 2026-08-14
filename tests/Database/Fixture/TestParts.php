<?php
namespace Tests\Database\Fixture;

use Tests\Database\Fixture\Schema\TestPartSchema;

/**
 * The Parts of a Thing, over a Schema that cannot delete
 *
 * The generator writes no deleteEntity for a Model without canDelete, so the
 * delete of the Schema is reached from here instead, which is what answers
 * false rather than marking anything.
 */
class TestParts extends TestPartSchema {

    /**
     * Adds a Part to a Thing
     * @param int    $testThingID
     * @param string $name
     * @return int
     */
    public static function add(int $testThingID, string $name): int {
        return self::createEntity(
            testThingID: $testThingID,
            name:        $name,
        );
    }

    /**
     * Marks a Part as deleted, which a Model with no canDelete does not
     * @param int $testPartID
     * @return bool
     */
    public static function drop(int $testPartID): bool {
        return self::deleteSchemaEntity($testPartID);
    }
}
