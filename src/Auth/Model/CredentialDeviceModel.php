<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Credential Device Model
 */
#[Model(
    hasTimestamps: true,
    canEdit:       true,
)]
class CredentialDeviceModel {

    #[Field(isPrimary: true)]
    public int $credentialID = 0;

    #[Field(isPrimary: true)]
    public string $userAgent = "";

    #[Field(isPrimary: true)]
    public string $playerID = "";

}
