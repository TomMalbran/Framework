<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Date\Date;

/**
 * The Credential Spam Model
 */
#[Model(
    description: "The addresses that asked for too much too fast, so they can be refused.",
    canEdit:     true,
)]
class CredentialSpamModel {

    #[Field(isPrimary: true)]
    public string $ip = "";

    #[Field]
    public ?Date $time = null;
}
