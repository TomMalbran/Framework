<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Expression;

/**
 * The Email Queue Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class EmailQueueModel {

    #[Field(isID: true)]
    public int $emailID = 0;

    public string $templateCode = "";

    #[Field(isJSON: true)]
    public string $sendTo = "";

    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    public string $emailResult = "";

    public int $sendTime = 0;

    public int $sentTime = 0;

    public int $dataID = 0;



    #[Expression("IF(emailResult = 'NotProcessed', 1, 0)")]
    public bool $isPending = false;

    #[Expression("IF(emailResult <> 'Sent', 1, 0)")]
    public bool $isError = false;

}
