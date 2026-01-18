<?php
namespace Framework\Auth;

use Framework\Auth\Schema\CredentialRefreshTokenSchema;
use Framework\Auth\Schema\CredentialRefreshTokenEntity;
use Framework\Auth\Schema\CredentialRefreshTokenQuery;
use Framework\Utils\Strings;
use Framework\Utils\Server;

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
     * @param int $credentialID
     * @return CredentialRefreshTokenEntity[]
     */
    public static function getAllForCredential(int $credentialID): array {
        $query = new CredentialRefreshTokenQuery();
        $query->credentialID->equal($credentialID);
        $query->createdTime->orderByAsc();
        return self::getEntityList($query);
    }



    /**
     * Creates a Refresh Token
     * @param int $credentialID
     * @param int $expiration
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
     * Updates the expiration of a Refresh Token
     * @param string $refreshToken
     * @param int    $expiration
     * @return string
     */
    public static function update(string $refreshToken, int $expiration): string {
        $query = new CredentialRefreshTokenQuery();
        $query->refreshToken->equal($refreshToken);

        self::editEntity(
            $query,
            expirationTime: time() + $expiration,
        );
        return $refreshToken;
    }

    /**
     * Removes a Refresh Token
     * @param string $refreshToken
     * @return bool
     */
    public static function remove(string $refreshToken): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->refreshToken->equal($refreshToken);
        return self::removeEntity($query);
    }

    /**
     * Removes all the Refresh Tokens for the given Credential
     * @param int $credentialID
     * @return bool
     */
    public static function removeAll(int $credentialID): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->credentialID->equal($credentialID);
        return self::removeEntity($query);
    }

    /**
     * Removes the old Refresh Tokens
     * @return bool
     */
    public static function removeOld(): bool {
        $query = new CredentialRefreshTokenQuery();
        $query->expirationTime->lessThan(time());
        return self::removeEntity($query);
    }
}
