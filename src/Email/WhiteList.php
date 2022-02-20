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
class WhiteList {

    private static $loaded = false;
    private static $schema = null;


    /**
     * Loads the Email WhiteList Schema
     * @return Schema
     */
    public static function getSchema(): Schema {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("emailWhiteList");
        }
        return self::$schema;
    }



    /**
     * Returns an Email from the White List with the given ID
     * @param integer $emailID
     * @return Model
     */
    public static function getOne(int $emailID): Model {
        return self::getSchema()->getOne($emailID);
    }

    /**
     * Returns true if the given Email exists in the White List
     * @param integer $emailID
     * @return boolean
     */
    public static function exists(int $emailID): bool {
        return self::getSchema()->exists($emailID);
    }

    /**
     * Returns true if the given Email exists in the White List
     * @param string $email
     * @return boolean
     */
    public static function emailExists(string $email): bool {
        $query = Query::create("email", "=", $email);
        return self::getSchema()->exists($query);
    }



    /**
     * Returns all the Emails in the White List
     * @param Request $request
     * @return array
     */
    public static function getAll(Request $request): array {
        return self::getSchema()->getAll(null, $request);
    }

    /**
     * Returns the total amount of Emails in the White List
     * @return integer
     */
    public static function getTotal(): int {
        return self::getSchema()->getTotal();
    }



    /**
     * Adds the given Email to the White List
     * @param Request $request
     * @return boolean
     */
    public static function add(Request $request): bool {
        return self::getSchema()->create($request);
    }

    /**
     * Edits the given Email in the White List
     * @param integer $emailID
     * @param Request $request
     * @return boolean
     */
    public static function edit(int $emailID, Request $request): bool {
        return self::getSchema()->edit($emailID, $request);
    }

    /**
     * Remove the given Email from the White List
     * @param integer $emailID
     * @return boolean
     */
    public static function remove(int $emailID): bool {
        return self::getSchema()->remove($emailID);
    }
}
