<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Date\Date;

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

    #[Field]
    public bool $isResolved = false;

    #[Field]
    public int $elapsedTime = 0;

    #[Field]
    public int $totalTime = 0;

    #[Field]
    public ?Date $updatedTime = null;

    #[Field]
    public int $updatedUser = 0;
}
