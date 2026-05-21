<?php
namespace Framework\Log\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Auth\Model\CredentialDeviceModel;
use Framework\Date\Date;
use Framework\Date\Type\DateType;

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



    #[Requested]
    public string $search = "";

    #[Requested(dateType: DateType::Start)]
    public ?Date $fromDate = null;

    #[Requested(dateType: DateType::End)]
    public ?Date $toDate = null;
}
