<?php
namespace Framework\Email;

use Framework\Email\Schema\EmailWhiteListSchema;
use Framework\Email\Schema\EmailWhiteListRequest;
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
     * @param EmailWhiteListRequest $request
     * @return int
     */
    public static function add(EmailWhiteListRequest $request): int {
        return self::createEntity($request);
    }

    /**
     * Edits the given Email in the White List
     * @param int                   $emailID
     * @param EmailWhiteListRequest $request
     * @return bool
     */
    public static function edit(int $emailID, EmailWhiteListRequest $request): bool {
        return self::editEntity($emailID, $request);
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
