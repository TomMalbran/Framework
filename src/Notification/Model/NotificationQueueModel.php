<?php
namespace Framework\Notification\Model;

use Framework\Auth\Model\CredentialModel;
use Framework\Notification\NotificationResult;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Relation;
use Framework\Date\Date;
use Framework\Utils\JSON;

/**
 * The Notification Queue Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class NotificationQueueModel {

    #[Field(isID: true), Requested(isMultiID: true)]
    public int $notificationQueueID = 0;

    #[Field]
    public int $credentialID = 0;

    #[Field]
    public int $currentUser = 0;

    #[Field]
    public string $title = "";

    #[Field]
    public string $message = "";

    #[Field]
    public string $url = "";

    #[Field]
    public string $dataType = "";

    #[Field]
    public int $dataID = 0;

    #[Field]
    public NotificationResult $notificationResult = NotificationResult::None;

    #[Field]
    public string $externalID = "";

    #[Field]
    public ?JSON $playerIDs = null;

    #[Field]
    public ?Date $sentTime = null;

    #[Field]
    public bool $isRead = false;

    #[Field]
    public bool $isDiscarded = false;



    #[Expression("IF(notificationResult = 'NotProcessed', 1, 0)")]
    public bool $isPending = false;

    #[Expression("IF(notificationResult <> 'Sent', 1, 0)")]
    public bool $isError = false;



    #[Relation(fieldNames: [ "name", "firstName", "lastName" ])]
    public ?CredentialModel $credential = null;
}
