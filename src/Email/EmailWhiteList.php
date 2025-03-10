<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Schema\EmailWhiteListSchema;
use Framework\Schema\EmailWhiteListQuery;

/**
 * The Email White List
 */
class EmailWhiteList extends EmailWhiteListSchema {

    /**
     * Returns true if the given Email exists in the White List
     * @param string  $email
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function emailExists(string $email, int $skipID = 0): bool {
        $query = new EmailWhiteListQuery();
        $query->email->equal($email);
        $query->emailID->notEqualIf($skipID);
        return self::entityExists($query);
    }



    /**
     * Adds the given Email to the White List
     * @param Request $request
     * @return integer
     */
    public static function add(Request $request): int {
        return self::createEntity($request);
    }

    /**
     * Edits the given Email in the White List
     * @param integer $emailID
     * @param Request $request
     * @return boolean
     */
    public static function edit(int $emailID, Request $request): bool {
        return self::editEntity($emailID, $request);
    }

    /**
     * Remove the given Email from the White List
     * @param integer $emailID
     * @return boolean
     */
    public static function remove(int $emailID): bool {
        return self::removeEntity($emailID);
    }
}
