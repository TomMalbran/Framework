<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Date\Date;

/**
 * The Credential Refresh Token Model
 */
#[Model(
    description:   "The tokens handed out at login, so a session renews without the password.",
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class CredentialRefreshTokenModel {

    #[Field]
    public int $credentialID = 0;

    #[Field(isPrimary: true)]
    public string $refreshToken = "";

    #[Field]
    public string $userAgent = "";

    #[Field]
    public ?Date $expirationTime = null;
}
