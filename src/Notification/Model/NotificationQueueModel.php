<?php
namespace Framework\Notification\Model;

use Framework\Auth\Model\CredentialModel;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Relation;

/**
 * The Notification Queue Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class NotificationQueueModel {

    #[Field(isID: true)]
    public int $notificationID = 0;

    public int $credentialID = 0;

    public int $currentUser = 0;

    public string $title = "";

    public string $body = "";

    public string $url = "";

    public string $dataType = "";

    public int $dataID = 0;

    public string $notificationResult = "";

    public string $externalID = "";

    public int $sentTime = 0;

    public bool $isRead = false;

    public bool $isDiscarded = false;



    #[Expression("IF(notificationResult = 'NotProcessed', 1, 0)")]
    public bool $isPending = false;

    #[Expression("IF(notificationResult <> 'Sent', 1, 0)")]
    public bool $isError = false;



    #[Relation(fieldNames: [ "name", "firstName", "lastName" ])]
    public ?CredentialModel $credential = null;

}
