<?php
namespace Framework\Log\Model;

use Framework\Auth\Model\CredentialModel;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Relation;

/**
 * The Log Session Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class LogSessionModel {

    #[Field(isID: true)]
    public int $sessionID = 0;

    #[Field(isKey: true)]
    public int $credentialID = 0;

    #[Field]
    public int $currentUser = 0;

    #[Field]
    public string $ip = "";

    #[Field]
    public string $userAgent = "";

    #[Field]
    public bool $isOpen = false;



    #[Relation(fieldNames: [ "name", "firstName", "lastName", "email" ])]
    public ?CredentialModel $credential = null;
}
