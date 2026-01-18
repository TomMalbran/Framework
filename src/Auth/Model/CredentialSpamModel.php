<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Credential Spam Model
 */
#[Model(
    canEdit: true,
)]
class CredentialSpamModel {

    #[Field(isPrimary: true)]
    public string $ip = "";

    public int $time = 0;
}
