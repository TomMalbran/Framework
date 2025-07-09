<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Log Error Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class LogErrorModel {

    #[Field(isID: true)]
    public int $logID = 0;

    public int $errorCode = 0;

    public string $errorText = "";

    public int $errorLevel = 0;

    public string $environment = "";

    public string $file = "";

    public int $line = 0;

    #[Field(isLongText: true)]
    public string $description = "";

    #[Field(isLongText: true)]
    public string $backtrace = "";

    public int $amount = 0;

    public bool $isResolved = false;

    public int $updatedTime = 0;

}
