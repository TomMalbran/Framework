<?php
namespace Framework\Auth;

use Framework\Schema\Factory;
use Framework\Schema\Query;
use Framework\Utils\Strings;

/**
 * The Auth Reset
 */
class Reset {
    
    private static $loaded = false;
    private static $schema = null;
    
    
    /**
     * Loads the Reset Schema
     * @return Schema
     */
    public static function getSchema() {
        if (!self::$loaded) {
            self::$loaded = false;
            self::$schema = Factory::getSchema("resets");
        }
        return self::$schema;
    }
    
    
    
    /**
     * Returns the Credential ID for the given Code
     * @param string $code
     * @return integer
     */
    public static function getCredentialID($code) {
        $query = Query::create("code", "=", $code);
        return self::getSchema()->getValue($query, "CREDENTIAL_ID");
    }
    
    /**
     * Returns true if the given code exists
     * @param string $code
     * @return boolean
     */
    public static function codeExists($code) {
        $query = Query::create("level", "=", $level);
        return self::getSchema()->exists($query);
    }
    
    
    
    /**
     * Creates and saves a recover code for the given Credential
     * @param integer $credentialID
     * @return string
     */
    public static function create($credentialID) {
        $code = Strings::randomCode(6, "ud");
        self::getSchema()->replace([
            "CREDENTIAL_ID" => $credentialID,
            "code"          => $code,
            "time"          => time(),
        ]);
        return $code;
    }
    
    /**
     * Deletes the reset data for the given Credential
     * @param integer $credentialID
     * @return void
     */
    public static function delete($credentialID) {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        self::getSchema()->delete($query);
    }
    
    /**
     * Deletes the old reset data for all the Credentials
     * @return void
     */
    public static function deleteOld() {
        $query = Query::create("time", "<", time() - 900);
        self::getSchema()->delete($query);
    }
}
