<?php
namespace Tests\Database\Rules;

use Tests\Database\Rules\Schema\RuleTagSchema;

/**
 * The Tags, the class an App writes over the generated Schema
 *
 * A belongsTo names the class the value is looked up in, which is one of the
 * App rather than the Model, since the Model itself has no exists.
 */
class RuleTags extends RuleTagSchema {

    /**
     * Adds a Tag
     * @param string $name
     * @return int
     */
    public static function add(string $name): int {
        return self::createEntity(name: $name);
    }
}
