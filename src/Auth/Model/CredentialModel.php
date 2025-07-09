<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Virtual;
use Framework\System\Status;

/**
 * The Credential Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
    canDelete:     true,
)]
class CredentialModel {

    #[Field(isID: true)]
    public int $credentialID = 0;

    #[Field(canEdit: false)]
    public int $currentUser = 0;

    public string $email = "";

    #[Field(canEdit: false)]
    public string $name = "";

    public string $firstName = "";

    public string $lastName = "";

    #[Field(noExists: true)]
    public string $phone = "";

    public string $language = "";

    #[Field(canEdit: false)]
    public string $avatar = "";

    #[Field(canEdit: false)]
    public string $appearance = "";

    #[Field(canEdit: false)]
    public string $access = "";

    #[Field(canEdit: false)]
    public string $password = "";

    #[Field(canEdit: false)]
    public string $salt = "";

    #[Field(canEdit: false)]
    public bool $reqPassChange = false;

    #[Field(canEdit: false)]
    public int $passExpiration = 0;

    #[Field(canEdit: false)]
    public string $accessToken = "";

    #[Field(canEdit: false)]
    public int $tokenExpiration = 0;

    #[Field(isText: true, noExists: true)]
    public string $observations = "";

    #[Field(noExists: true)]
    public bool $sendEmails = false;

    #[Field(noExists: true)]
    public bool $sendEmailNotis = false;

    #[Field(noExists: true)]
    public bool $sendTickets = false;

    #[Field(isSigned: true, canEdit: false)]
    public int $timezone = 0;

    #[Field(canEdit: false)]
    public int $currentLogin = 0;

    #[Field(canEdit: false)]
    public int $lastLogin = 0;

    #[Field(canEdit: false)]
    public bool $askNotifications = true;

    #[Field(canEdit: false)]
    public int $progressValue = 0;

    public Status $status = Status::None;



    #[Virtual]
    public int $adminID = 0;

    #[Virtual]
    public string $userAccess = "";

    #[Virtual]
    public string $avatarFile = "";

    #[Virtual]
    public string $accessName = "";

}
