<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Credential Refresh Token Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class CredentialRefreshTokenModel {

    public int $credentialID = 0;

    #[Field(isPrimary: true)]
    public string $refreshToken = "";

    public string $userAgent = "";

    public int $expirationTime = 0;

}
