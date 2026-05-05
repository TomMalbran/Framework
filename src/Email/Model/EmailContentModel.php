<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\System\EmailCode;

/**
 * The Email Content Model
 */
#[Model(
    canCreate: true,
)]
class EmailContentModel {

    #[Field(isID: true), Requested]
    public int $emailContentID = 0;

    public EmailCode $emailCode = EmailCode::None;

    public string $language = "";

    public string $languageName = "";

    public string $description = "";

    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    public int $position = 0;
}
