<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
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

    #[Field(isID: true)]
    public int $logID = 0;

    #[Field(isText: true)]
    public string $expression = "";

    public string $environment = "";

    public int $amount = 0;

    public bool $isResolved = false;

    public int $elapsedTime = 0;

    public int $totalTime = 0;

    public ?Date $updatedTime = null;

    public int $updatedUser = 0;
}
