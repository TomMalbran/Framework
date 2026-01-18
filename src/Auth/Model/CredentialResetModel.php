<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Credential Reset Model
 */
#[Model(
    canEdit: true,
)]
class CredentialResetModel {

    #[Field(isPrimary: true)]
    public int $credentialID = 0;

    #[Field(isPrimary: true)]
    public string $email = "";

    public string $resetCode = "";

    public int $time = 0;
}
