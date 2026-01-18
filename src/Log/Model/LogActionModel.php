<?php
namespace Framework\Log\Model;

use Framework\Auth\Model\CredentialModel;
use Framework\Log\Model\LogSessionModel;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Relation;

/**
 * The Log Action Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
)]
class LogActionModel {

    #[Field(isID: true)]
    public int $actionID = 0;

    #[Field(isKey: true)]
    public int $sessionID = 0;

    #[Field(isKey: true)]
    public int $credentialID = 0;

    public int $currentUser = 0;

    public string $module = "";

    public string $action = "";

    #[Field(isText: true)]
    public string $dataID = "";



    #[Relation(fieldNames: [ "createdTime", "ip", "userAgent" ])]
    public ?LogSessionModel $session = null;

    #[Relation(fieldNames: [ "name", "firstName", "lastName", "email" ])]
    public ?CredentialModel $credential = null;
}
