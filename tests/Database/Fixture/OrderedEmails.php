<?php
namespace Tests\Database\Fixture;

use Framework\Email\Schema\EmailContentColumn;
use Framework\Email\Schema\EmailContentQuery;
use Framework\Email\Schema\EmailContentSchema;
use Framework\System\EmailCode;

/**
 * The Email Content, with the ordered writes of the Schema opened up
 *
 * The Framework never calls these itself — its own migration writes the rows
 * with skipOrder — but an App class over a Model with a position does, the
 * same way EmailWhiteList opens up the plain ones. This stands in for that.
 */
class OrderedEmails extends EmailContentSchema {

    /**
     * Adds a row, letting the Schema give it the next position
     * @param EmailCode $emailCode
     * @param string    $language
     * @param string    $description
     * @return int
     */
    public static function add(EmailCode $emailCode, string $language, string $description): int {
        return self::createEntity(
            emailCode:   $emailCode,
            language:    $language,
            description: $description,
        );
    }

    /**
     * Removes a row, closing the gap it leaves
     * @param int $emailContentID
     * @return bool
     */
    public static function drop(int $emailContentID): bool {
        return self::removeEntity($emailContentID);
    }

    /**
     * Returns the rows as the options of a select
     * @param EmailContentQuery $query
     * @return list<\Framework\IO\Select>
     */
    public static function select(EmailContentQuery $query): array {
        return self::getEntitySelect(
            query:      $query,
            nameColumn: EmailContentColumn::Description,
            idColumn:   EmailContentColumn::EmailContentID,
        );
    }

    /**
     * Empties the table, which is what a content migration does first
     * @return bool
     */
    public static function empty(): bool {
        return self::truncateData();
    }
}
