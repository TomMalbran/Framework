<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Virtual;
use Framework\Database\Model\Requested;
use Framework\Database\Status\Status;
use Framework\System\Access;
use Framework\Date\Date;

/**
 * The Credential Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
    canDelete:     true,
    skipList:      true,
)]
class CredentialModel {

    #[Field(isID: true)]
    public int $credentialID = 0;

    public int $currentUser = 0;

    public string $name = "";


    #[Field, Requested]
    public string $firstName = "";

    #[Field, Requested]
    public string $lastName = "";

    #[Field, Requested]
    public string $email = "";

    #[Field(noExists: true), Requested]
    public string $phone = "";

    #[Field, Requested]
    public string $language = "";

    #[Field(noExists: true, isText: true), Requested]
    public string $observations = "";

    #[Field(noExists: true), Requested]
    public bool $sendEmails = false;

    #[Field(noExists: true), Requested]
    public bool $sendEmailNotis = false;

    #[Field(noExists: true), Requested]
    public bool $sendTickets = false;

    #[Field(isSigned: true), Requested]
    public int $timezone = 0;


    public string $avatar = "";

    public string $appearance = "";

    public Access $access = Access::None;

    public string $password = "";

    public string $salt = "";

    public bool $reqPassChange = false;

    public ?Date $passExpiration = null;

    public string $accessToken = "";

    public ?Date $tokenExpiration = null;

    public ?Date $currentLogin = null;

    public ?Date $lastLogin = null;

    public bool $askNotifications = true;

    public int $progressValue = 0;

    public Status $status = Status::None;



    #[Virtual]
    public int $adminID = 0;

    #[Virtual]
    public Access $userAccess = Access::None;

    #[Virtual]
    public string $avatarFile = "";

    #[Virtual]
    public string $accessName = "";
}
