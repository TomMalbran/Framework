<?php
namespace Tests\Database\Rules;

use Tests\Database\Rules\Schema\RuleSchema;

/**
 * The Rules, the class an App writes over the generated Schema
 */
class Rules extends RuleSchema {

    public static bool $canEdit = true;


    /**
     * Says whether the rows can be edited, which the validation asks first
     * @return bool
     */
    public static function canEdit(): bool {
        return self::$canEdit;
    }

    /**
     * Adds a row with the values the unique rules are about
     * @param string $name
     * @param int    $serial
     * @param string $otherEmail
     * @param int    $ticket     Optional.
     * @return int
     */
    public static function add(string $name, int $serial, string $otherEmail, int $ticket = 0): int {
        return self::createEntity(
            name:       $name,
            serial:     $serial,
            otherEmail: $otherEmail,
            ticket:     $ticket,
        );
    }
}
