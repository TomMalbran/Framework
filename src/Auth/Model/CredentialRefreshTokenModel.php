<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Date\Date;

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

    public ?Date $expirationTime = null;
}
