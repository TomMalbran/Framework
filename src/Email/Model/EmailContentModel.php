<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Email Content Model
 */
#[Model(
    canCreate: true,
)]
class EmailContentModel {

    #[Field(isID: true)]
    public int $emailContentID = 0;

    public string $emailCode = "";

    public string $language = "";

    public string $languageName = "";

    public string $description = "";

    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    public int $position = 0;
}
