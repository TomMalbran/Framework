<?php
namespace Framework\Core;

use Framework\Core\Schema\MigrationsSchema;
use Framework\Core\Schema\MigrationsQuery;

/**
 * The Migration Data
 */
class MigrationData extends MigrationsSchema {

    /**
     * Returns the Names of the Migrations that were already applied
     * @return list<string>
     */
    public static function getAppliedNames(): array {
        if (!self::tableExists()) {
            return [];
        }

        $query = new MigrationsQuery();
        $query->name->orderByAsc();

        $result = [];
        foreach (self::getEntityList($query) as $elem) {
            $result[] = $elem->name;
        }
        return $result;
    }

    /**
     * Returns true if there is no Migration applied yet
     * @return bool
     */
    public static function isEmpty(): bool {
        if (!self::tableExists()) {
            return true;
        }
        return self::getEntityTotal() === 0;
    }

    /**
     * Stores the given Migration as applied
     * @param string $name
     * @param string $title
     * @return void
     */
    public static function add(string $name, string $title): void {
        if (!self::tableExists()) {
            return;
        }

        self::createEntity(
            name:  $name,
            title: $title,
        );
    }
}
