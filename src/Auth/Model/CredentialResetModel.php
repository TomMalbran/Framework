<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Date\Date;

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

    public ?Date $time = null;
}
