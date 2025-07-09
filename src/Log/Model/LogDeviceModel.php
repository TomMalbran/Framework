<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Log Device Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
)]
class LogDeviceModel {

    #[Field(isID: true)]
    public int $logID = 0;

    public int $credentialID = 0;

    public string $userAgent = "";

    public string $playerID = "";

    public bool $wasAdded = false;

}
