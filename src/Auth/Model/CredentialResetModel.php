<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Date\Date;

/**
 * The Credential Reset Model
 */
#[Model(
    description: "The pending password resets, with the code sent by email and its time.",
    canEdit:     true,
)]
class CredentialResetModel {

    #[Field(isPrimary: true)]
    public int $credentialID = 0;

    #[Field(isPrimary: true)]
    public string $email = "";

    #[Field]
    public string $resetCode = "";

    #[Field]
    public ?Date $time = null;
}
