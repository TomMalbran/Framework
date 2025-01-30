<?php
namespace Framework\Auth;

use Framework\Database\Query;
use Framework\Utils\DateTime;
use Framework\Utils\Strings;
use Framework\Schema\CredentialResetSchema;

/**
 * The Auth Reset
 */
class Reset extends CredentialResetSchema {

    /**
     * Returns the Credential ID for the given Code
     * @param string $code
     * @return integer
     */
    public static function getCredentialID(string $code): int {
        $query = Query::create("code", "=", $code);
        return (int)self::getEntityValue($query, "CREDENTIAL_ID");
    }

    /**
     * Returns the Email for the given Code
     * @param string $code
     * @return string
     */
    public static function getEmail(string $code): string {
        $query = Query::create("code", "=", $code);
        return self::getEntityValue($query, "email");
    }

    /**
     * Returns true if the given code exists
     * @param string $code
     * @param string $email Optional.
     * @return boolean
     */
    public static function codeExists(string $code, string $email = ""): bool {
        $query = Query::create("code", "=", $code);
        $query->addIf("email", "=", $email);
        return self::entityExists($query);
    }



    /**
     * Creates and saves a recover code for the given Credential
     * @param integer $credentialID  Optional.
     * @param string  $email         Optional.
     * @param string  $availableSets Optional.
     * @return string
     */
    public static function create(int $credentialID = 0, string $email = "", string $availableSets = "ud"): string {
        $code = Strings::randomCode(6, $availableSets);
        self::replaceEntity(
            credentialID: $credentialID,
            email:        $email,
            code:         $code,
            time:         time(),
        );
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
        return self::removeEntity($query);
    }

    /**
     * Deletes the old reset data for all the Credentials
     * @return boolean
     */
    public static function deleteOld(): bool {
        $query = Query::create("time", "<", DateTime::getLastXHours(3));
        return self::removeEntity($query);
    }
}
