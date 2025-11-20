<?php
namespace Framework\Auth\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Virtual;
use Framework\Database\Status\Status;

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

    public int $currentUser = 0;

    public string $name = "";


    #[Field(fromRequest: true)]
    public string $firstName = "";

    #[Field(fromRequest: true)]
    public string $lastName = "";

    #[Field(fromRequest: true)]
    public string $email = "";

    #[Field(fromRequest: true, noExists: true)]
    public string $phone = "";

    #[Field(fromRequest: true)]
    public string $language = "";

    #[Field(fromRequest: true, noExists: true, isText: true)]
    public string $observations = "";

    #[Field(fromRequest: true, noExists: true)]
    public bool $sendEmails = false;

    #[Field(fromRequest: true, noExists: true)]
    public bool $sendEmailNotis = false;

    #[Field(fromRequest: true, noExists: true)]
    public bool $sendTickets = false;

    #[Field(fromRequest: true, isSigned: true)]
    public int $timezone = 0;


    public string $avatar = "";

    public string $appearance = "";

    public string $access = "";

    public string $password = "";

    public string $salt = "";

    public bool $reqPassChange = false;

    public int $passExpiration = 0;

    public string $accessToken = "";

    public int $tokenExpiration = 0;

    public int $currentLogin = 0;

    public int $lastLogin = 0;

    public bool $askNotifications = true;

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
