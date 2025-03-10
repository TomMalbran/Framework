<?php
namespace Framework\Auth;

use Framework\Utils\Strings;
use Framework\Utils\Server;
use Framework\Schema\CredentialRefreshTokenSchema;
use Framework\Schema\CredentialRefreshTokenEntity;
use Framework\Schema\CredentialRefreshTokenQuery;

/**
 * The Refresh Tokens
 */
class RefreshToken extends CredentialRefreshTokenSchema {

    /**
     * Returns the Refresh Token Entity with the given Refresh Token
     * @param string $refreshToken
     * @return CredentialRefreshTokenEntity
     */
    public static function get(string $refreshToken): CredentialRefreshTokenEntity {
        $query = new CredentialRefreshTokenQuery();
        $query->refreshToken->equal($refreshToken);
        return self::getEntity($query);
    }

    /**
     * Returns all the Refresh Tokens for the given Credential
     * @param integer $credentialID
     * @return CredentialRefreshTokenEntity[]
     */
    public static function getAllForCredential(int $credentialID): array {
        $query = new CredentialRefreshTokenQuery();
        $query->credentialID->equal($credentialID);
        $query->createdTime->orderByAsc();
        return self::getEntityList($query);
    }



    /**
     * Adds a Refresh Token
     * @param integer $credentialID
     * @param integer $expiration
     * @return string
     */
    public static function create(int $credentialID, int $expiration): string {
        $refreshToken = Strings::randomCode(20);
        self::createEntity(
            credentialID:   $credentialID,
            userAgent:      Server::getUserAgent(),
            refreshToken:   $refreshToken,
            expirationTime: time() + $expiration,
        );
        return $refreshToken;
    }

    /**
     * Recreates a Refresh Token
     * @param string  $refreshToken
     * @param integer $expiration
     * @return string
     */
    public static function recreate(string $refreshToken, int $expiration): string {
        $query = new CredentialRefreshTokenQuery();
        $query->refreshToken->equal($refreshToken);

        $newRefreshToken = Strings::randomCode(20);
        self::editEntity(
            $query,
            refreshToken:   $newRefreshToken,
            expirationTime: time() + $expiration,
        );
        return $newRefreshToken;
    }

    /**
     * Removes a Refresh Token
     * @param string $refreshToken
     * @return boolean
     */
    public static function remove(string $refreshToken): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->refreshToken->equal($refreshToken);
        return self::removeEntity($query);
    }

    /**
     * Removes all the Refresh Tokens for the given Credential
     * @param integer $credentialID
     * @return boolean
     */
    public static function removeAll(int $credentialID): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->credentialID->equal($credentialID);
        return self::removeEntity($query);
    }

    /**
     * Removes the old Refresh Tokens
     * @param integer $expiration
     * @return boolean
     */
    public static function removeOld(int $expiration): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->expirationTime->lessThan(time() - $expiration);
        return self::removeEntity($query);
    }
}
