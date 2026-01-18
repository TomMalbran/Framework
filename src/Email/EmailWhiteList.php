<?php
namespace Framework\Email;

use Framework\Email\Schema\EmailWhiteListSchema;
use Framework\Email\Schema\EmailWhiteListQuery;

/**
 * The Email White List
 */
class EmailWhiteList extends EmailWhiteListSchema {

    /**
     * Returns true if the given Email exists in the White List
     * @param string $email
     * @param int    $skipID Optional.
     * @return bool
     */
    public static function emailExists(string $email, int $skipID = 0): bool {
        $query = new EmailWhiteListQuery();
        $query->email->equal($email);
        $query->emailID->notEqualIf($skipID);
        return self::entityExists($query);
    }



    /**
     * Adds the given Email to the White List
     * @param string $email
     * @param string $description
     * @return int
     */
    public static function add(string $email, string $description): int {
        return self::createEntity(
            email:       $email,
            description: $description,
        );
    }

    /**
     * Edits the given Email in the White List
     * @param int    $emailID
     * @param string $email
     * @param string $description
     * @return bool
     */
    public static function edit(int $emailID, string $email, string $description): bool {
        return self::editEntity(
            $emailID,
            email:       $email,
            description: $description,
        );
    }

    /**
     * Remove the given Email from the White List
     * @param int $emailID
     * @return bool
     */
    public static function remove(int $emailID): bool {
        return self::removeEntity($emailID);
    }
}
