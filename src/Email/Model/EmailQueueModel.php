<?php
namespace Framework\Email\Model;

use Framework\Email\EmailResult;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Database\Model\Expression;
use Framework\System\EmailCode;
use Framework\Date\Date;
use Framework\Date\Type\DateType;
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

    #[Field(isID: true), Requested(isMultiID: true)]
    public int $emailQueueID = 0;

    #[Field]
    public EmailCode $emailCode = EmailCode::None;

    #[Field]
    public ?JSON $sendTo = null;

    #[Field]
    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    #[Field]
    public EmailResult $emailResult = EmailResult::None;

    #[Field, Requested(isNative: true)]
    public int $dataID = 0;


    #[Field]
    public ?Date $sendTime = null;

    #[Field]
    public ?Date $sentTime = null;



    #[Requested(isNative: true)]
    public string $search = "";

    #[Requested(isNative: true, dateType: DateType::Start, hourInput: "fromHour")]
    public ?Date $fromDate = null;

    #[Requested(isNative: true, dateType: DateType::End, hourInput: "toHour")]
    public ?Date $toDate = null;

    /** @var list<string> */
    #[Requested(isNative: true)]
    public array $results = [];





    #[Expression("IF(emailResult = 'NotProcessed', 1, 0)")]
    public bool $isPending = false;

    #[Expression("IF(emailResult <> 'Sent', 1, 0)")]
    public bool $isError = false;
}
