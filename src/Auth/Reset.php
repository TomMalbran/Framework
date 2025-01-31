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
     * @param string $resetCode
     * @return integer
     */
    public static function getCredentialID(string $resetCode): int {
        $query = Query::create("resetCode", "=", $resetCode);
        return (int)self::getEntityValue($query, "CREDENTIAL_ID");
    }

    /**
     * Returns the Email for the given Code
     * @param string $resetCode
     * @return string
     */
    public static function getEmail(string $resetCode): string {
        $query = Query::create("resetCode", "=", $resetCode);
        return self::getEntityValue($query, "email");
    }

    /**
     * Returns true if the given Reset Code exists
     * @param string $resetCode
     * @param string $email     Optional.
     * @return boolean
     */
    public static function codeExists(string $resetCode, string $email = ""): bool {
        $query = Query::create("resetCode", "=", $resetCode);
        $query->addIf("email", "=", $email);
        return self::entityExists($query);
    }



    /**
     * Creates and saves a Reset Code for the given Credential
     * @param integer $credentialID  Optional.
     * @param string  $email         Optional.
     * @param string  $availableSets Optional.
     * @return string
     */
    public static function create(int $credentialID = 0, string $email = "", string $availableSets = "ud"): string {
        $resetCode = Strings::randomCode(6, $availableSets);
        self::replaceEntity(
            credentialID: $credentialID,
            email:        $email,
            resetCode:    $resetCode,
            time:         time(),
        );
        return $resetCode;
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
