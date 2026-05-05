<?php
namespace Framework\Notification\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

/**
 * The Notification Content Model
 */
#[Model(
    canCreate: true,
)]
class NotificationContentModel {

    #[Field(isID: true), Requested]
    public int $notificationContentID = 0;

    public string $notificationCode = "";

    public string $language = "";

    public string $languageName = "";

    public string $description = "";

    public string $title = "";

    #[Field(isText: true)]
    public string $message = "";

    public int $position = 0;
}
