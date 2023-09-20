<?php
namespace Framework\Email;

use Framework\Request;
use Framework\Schema\Factory;
use Framework\Schema\Schema;
use Framework\Schema\Model;
use Framework\Schema\Query;

/**
 * The Email White List
 */
class EmailWhiteList {

    /**
     * Loads the Email WhiteList Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("emailWhiteList");
    }



    /**
     * Returns an Email from the White List with the given ID
     * @param integer $emailID
     * @return Model
     */
    public static function getOne(int $emailID): Model {
        return self::schema()->getOne($emailID);
    }

    /**
     * Returns true if the given Email exists in the White List
     * @param integer $emailID
     * @return boolean
     */
    public static function exists(int $emailID): bool {
        return self::schema()->exists($emailID);
    }

    /**
     * Returns true if the given Email exists in the White List
     * @param string  $email
     * @param integer $skipID Optional.
     * @return boolean
     */
    public static function emailExists(string $email, int $skipID = 0): bool {
        $query = Query::create("email", "=", $email);
        $query->addIf("EMAIL_ID", "<>", $skipID);
        return self::schema()->exists($query);
    }



    /**
     * Returns all the Emails in the White List
     * @param Request $request
     * @return array{}[]
     */
    public static function getAll(Request $request): array {
        return self::schema()->getAll(null, $request);
    }

    /**
     * Returns the total amount of Emails in the White List
     * @return integer
     */
    public static function getTotal(): int {
        return self::schema()->getTotal();
    }



    /**
     * Adds the given Email to the White List
     * @param Request $request
     * @return boolean
     */
    public static function add(Request $request): bool {
        return self::schema()->create($request);
    }

    /**
     * Edits the given Email in the White List
     * @param integer $emailID
     * @param Request $request
     * @return boolean
     */
    public static function edit(int $emailID, Request $request): bool {
        return self::schema()->edit($emailID, $request);
    }

    /**
     * Remove the given Email from the White List
     * @param integer $emailID
     * @return boolean
     */
    public static function remove(int $emailID): bool {
        return self::schema()->remove($emailID);
    }
}
