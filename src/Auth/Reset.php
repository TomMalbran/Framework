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

    /**
     * Loads the Reset Schema
     * @return Schema
     */
    public static function schema(): Schema {
        return Factory::getSchema("resets");
    }



    /**
     * Returns the Credential ID for the given Code
     * @param string $code
     * @return integer
     */
    public static function getCredentialID(string $code): int {
        $query = Query::create("code", "=", $code);
        return self::schema()->getValue($query, "CREDENTIAL_ID");
    }

    /**
     * Returns the Email for the given Code
     * @param string $code
     * @return string
     */
    public static function getEmail(string $code): string {
        $query = Query::create("code", "=", $code);
        return self::schema()->getValue($query, "email");
    }

    /**
     * Returns true if the given code exists
     * @param string $code
     * @return boolean
     */
    public static function codeExists(string $code): bool {
        $query = Query::create("code", "=", $code);
        return self::schema()->exists($query);
    }



    /**
     * Creates and saves a recover code for the given Credential
     * @param integer $credentialID Optional.
     * @param string  $email        Optional.
     * @return string
     */
    public static function create(int $credentialID = 0, string $email = ""): string {
        $code = Strings::randomCode(6, "ud");
        self::schema()->replace([
            "CREDENTIAL_ID" => $credentialID,
            "email"         => $email,
            "code"          => $code,
            "time"          => time(),
        ]);
        return $code;
    }

    /**
     * Deletes the reset data for the given Credential
     * @param integer $credentialID Optional.
     * @param string  $email        Optional.
     * @return boolean
     */
    public static function delete(int $credentialID = 0, string $email = ""): bool {
        $query = Query::createIf("CREDENTIAL_ID", "=", $credentialID);
        $query->addIf("email", "=", $email);
        return self::schema()->remove($query);
    }

    /**
     * Deletes the old reset data for all the Credentials
     * @return void
     */
    public static function deleteOld(): void {
        $schema = self::schema();
        if (!empty($schema)) {
            $query = Query::create("time", "<", time() - 3 * 3600);
            $schema->remove($query);
        }
    }
}
