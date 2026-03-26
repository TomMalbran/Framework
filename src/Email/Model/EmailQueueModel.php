<?php
namespace Framework\Email\Model;

use Framework\Email\EmailResult;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Expression;
use Framework\System\EmailCode;
use Framework\Date\Date;
use Framework\Utils\JSON;

/**
 * The Email Queue Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class EmailQueueModel {

    #[Field(isID: true)]
    public int $emailQueueID = 0;

    public EmailCode $emailCode = EmailCode::None;

    public ?JSON $sendTo = null;

    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    public EmailResult $emailResult = EmailResult::None;

    public int $dataID = 0;


    public ?Date $sendTime = null;

    public ?Date $sentTime = null;



    #[Expression("IF(emailResult = 'NotProcessed', 1, 0)")]
    public bool $isPending = false;

    #[Expression("IF(emailResult <> 'Sent', 1, 0)")]
    public bool $isError = false;
}
