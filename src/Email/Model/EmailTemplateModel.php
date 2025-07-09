<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Email Template Model
 */
#[Model(
    canCreate: true,
)]
class EmailTemplateModel {

    #[Field(isID: true)]
    public int $templateID = 0;

    public string $templateCode = "";

    public string $language = "";

    public string $languageName = "";

    public string $description = "";

    public string $subject = "";

    #[Field(isText: true)]
    public string $message = "";

    public int $position = 0;

}
