<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Date\Date;
use Framework\Date\Type\DateType;

/**
 * The Log Error Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class LogErrorModel {

    #[Field(isID: true), Requested(isMultiID: true)]
    public int $logID = 0;

    #[Field]
    public int $errorCode = 0;

    #[Field]
    public string $errorText = "";

    #[Field]
    public int $errorLevel = 0;

    #[Field]
    public string $environment = "";

    #[Field]
    public string $file = "";

    #[Field]
    public int $line = 0;

    #[Field(isLongText: true)]
    public string $description = "";

    #[Field(isLongText: true)]
    public string $backtrace = "";

    #[Field]
    public int $amount = 0;

    #[Field, Requested(isNative: true, isString: true)]
    public bool $isResolved = false;

    #[Field]
    public ?Date $updatedTime = null;



    #[Requested(isNative: true)]
    public string $search = "";

    #[Requested(isNative: true, dateType: DateType::Start, hourInput: "fromHour")]
    public ?Date $fromDate = null;

    #[Requested(isNative: true, dateType: DateType::End, hourInput: "toHour")]
    public ?Date $toDate = null;
}
