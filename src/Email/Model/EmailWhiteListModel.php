<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Email White List Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class EmailWhiteListModel {

    #[Field(isID: true)]
    public int $emailID = 0;

    public string $email = "";

    public string $description = "";

}
