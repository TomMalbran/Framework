<?php
namespace Framework\Auth;

use Framework\Schema\Factory;
use Framework\Schema\Schema;
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
    public static function getSchema(): Schema {
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
    public static function getCredentialID(string $code): int {
        $query = Query::create("code", "=", $code);
        return self::getSchema()->getValue($query, "CREDENTIAL_ID");
    }
    
    /**
     * Returns true if the given code exists
     * @param string $code
     * @return boolean
     */
    public static function codeExists(string $code): bool {
        $query = Query::create("level", "=", $level);
        return self::getSchema()->exists($query);
    }
    
    
    
    /**
     * Creates and saves a recover code for the given Credential
     * @param integer $credentialID
     * @return string
     */
    public static function create(int $credentialID): string {
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
     * @return boolean
     */
    public static function delete(int $credentialID): bool {
        $query = Query::create("CREDENTIAL_ID", "=", $credentialID);
        return self::getSchema()->remove($query);
    }
    
    /**
     * Deletes the old reset data for all the Credentials
     * @return boolean
     */
    public static function deleteOld(): bool {
        $query = Query::create("time", "<", time() - 900);
        return self::getSchema()->remove($query);
    }
}
