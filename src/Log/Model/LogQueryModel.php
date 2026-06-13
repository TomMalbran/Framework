<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Date\Date;
use Framework\Date\Type\DateType;

/**
 * The Log Query Model
 */
#[Model(
    hasUsers:      true,
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class LogQueryModel {

    #[Field(isID: true), Requested(isMultiID: true)]
    public int $logID = 0;

    #[Field(isText: true)]
    public string $expression = "";

    #[Field]
    public string $environment = "";

    #[Field]
    public int $amount = 0;

    #[Field, Requested(isString: true, canEdit: false)]
    public bool $isResolved = false;

    #[Field]
    public int $elapsedTime = 0;

    #[Field]
    public int $totalTime = 0;

    #[Field]
    public ?Date $updatedTime = null;

    #[Field]
    public int $updatedUser = 0;



    #[Requested]
    public string $search = "";

    #[Requested(dateType: DateType::Start, hourInput: "fromHour")]
    public ?Date $fromDate = null;

    #[Requested(dateType: DateType::End, hourInput: "toHour")]
    public ?Date $toDate = null;
}
