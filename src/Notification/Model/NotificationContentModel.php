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

    #[Field]
    public string $notificationCode = "";

    #[Field]
    public string $language = "";

    #[Field]
    public string $languageName = "";

    #[Field]
    public string $description = "";

    #[Field]
    public string $title = "";

    #[Field(isText: true)]
    public string $message = "";

    public int $position = 0;
}
