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

    #[Field]
    public int $currentUser = 0;

    #[Field, Requested(forValidate: true)]
    public string $name = "";


    #[Field, Requested(forValidate: true)]
    public string $firstName = "";

    #[Field, Requested(forValidate: true)]
    public string $lastName = "";

    #[Field, Requested(forValidate: true)]
    public string $email = "";

    #[Field, Requested(forValidate: true)]
    public string $phone = "";

    #[Field, Requested(forValidate: true)]
    public string $language = "";

    #[Field(isText: true), Requested(forValidate: true)]
    public string $observations = "";

    #[Field, Requested(forValidate: true)]
    public bool $sendEmails = false;

    #[Field, Requested(forValidate: true)]
    public bool $sendEmailNotis = false;

    #[Field, Requested(forValidate: true)]
    public bool $sendTickets = false;

    #[Field(isSigned: true), Requested(forValidate: true)]
    public int $timezone = 0;


    #[Field]
    public string $avatar = "";

    #[Field, Requested(canEdit: false)]
    public string $appearance = "";

    #[Field, Requested(forValidate: true)]
    public Access $access = Access::None;

    #[Field, Requested(forValidate: true)]
    public string $password = "";

    #[Field, Requested(forValidate: true)]
    public string $salt = "";

    #[Field, Requested(forValidate: true)]
    public bool $reqPassChange = false;

    #[Field]
    public ?Date $passExpiration = null;

    #[Field, Requested(canEdit: false)]
    public string $accessToken = "";

    #[Field]
    public ?Date $tokenExpiration = null;

    #[Field]
    public ?Date $currentLogin = null;

    #[Field]
    public ?Date $lastLogin = null;

    #[Field]
    public bool $askNotifications = true;

    #[Field]
    public int $progressValue = 0;

    #[Field, Requested]
    public Status $status = Status::None;



    #[Virtual]
    public int $adminID = 0;

    #[Virtual]
    public Access $userAccess = Access::None;

    #[Virtual]
    public string $avatarFile = "";

    #[Virtual]
    public string $accessName = "";



    #[Requested]
    public string $value = "";

    #[Requested]
    public string $code = "";

    #[Requested]
    public string $resetCode = "";

    #[Requested(forValidate: true)]
    public string $oldPassword = "";

    #[Requested(forValidate: true)]
    public string $newPassword = "";

    #[Requested(forValidate: true)]
    public string $confirmPassword = "";

    #[Requested(isFile: true)]
    public string $file = "";
}
