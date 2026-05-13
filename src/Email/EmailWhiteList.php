<?php
namespace Framework\Email;

use Framework\Email\Schema\EmailWhiteListSchema;
use Framework\Email\Schema\EmailWhiteListRequest;

/**
 * The Email White List
 */
class EmailWhiteList extends EmailWhiteListSchema {

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
