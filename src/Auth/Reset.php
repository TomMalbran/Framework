<?php
namespace Framework\Auth;

use Framework\Auth\Schema\CredentialResetSchema;
use Framework\Auth\Schema\CredentialResetColumn;
use Framework\Auth\Schema\CredentialResetQuery;
use Framework\Date\Date;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

/**
 * The Auth Reset
 */
class Reset extends CredentialResetSchema {

    /**
     * Returns the Credential ID for the given Code
     * @param string $resetCode
     * @return int
     */
    public static function getCredentialID(string $resetCode): int {
        $query = new CredentialResetQuery();
        $query->resetCode->equal($resetCode);

        $result = self::getEntityValue($query, CredentialResetColumn::CredentialID);
        return Numbers::toInt($result);
    }

    /**
     * Returns the Email for the given Code
     * @param string $resetCode
     * @return string
     */
    public static function getEmail(string $resetCode): string {
        $query = new CredentialResetQuery();
        $query->resetCode->equal($resetCode);

        $result = self::getEntityValue($query, CredentialResetColumn::Email);
        return Strings::toString($result);
    }

    /**
     * Returns true if the given Reset Code exists
     * @param string $resetCode
     * @param string $email     Optional.
     * @return bool
     */
    public static function codeExists(string $resetCode, string $email = ""): bool {
        $query = new CredentialResetQuery();
        $query->resetCode->equal($resetCode);
        $query->email->equalIf($email);
        return self::entityExists($query);
    }



    /**
     * Creates and saves a Reset Code for the given Credential
     * @param int    $credentialID  Optional.
     * @param string $email         Optional.
     * @param string $availableSets Optional.
     * @return string
     */
    public static function create(
        int $credentialID = 0,
        string $email = "",
        string $availableSets = "ud",
    ): string {
        $resetCode = Strings::randomCode(6, $availableSets);
        self::replaceEntity(
            credentialID: $credentialID,
            email:        $email,
            resetCode:    $resetCode,
            time:         Date::now(),
        );
        return $resetCode;
    }

    /**
     * Deletes the reset data for the given Credential
     * @param int    $credentialID Optional.
     * @param string $email        Optional.
     * @return bool
     */
    public static function delete(int $credentialID = 0, string $email = ""): bool {
        $query = new CredentialResetQuery();
        $query->credentialID->equal($credentialID);
        $query->email->equalIf($email);
        return self::removeEntity($query);
    }

    /**
     * Deletes the old reset data for all the Credentials
     * @return bool
     */
    public static function deleteOld(): bool {
        $time  = Date::now()->subtract(hours: 3);
        $query = new CredentialResetQuery();
        $query->time->lessThan($time);
        return self::removeEntity($query);
    }
}
