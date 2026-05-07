<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Auth\Model\CredentialDeviceModel;

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

    #[Field]
    public int $credentialID = 0;

    #[Field]
    public string $userAgent = "";

    #[Field(belongsTo: CredentialDeviceModel::class)]
    public string $playerID = "";

    #[Field]
    public bool $wasAdded = false;
}
