<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

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

    #[Field(isUnique: true), Requested(forValidate: true)]
    public string $email = "";

    #[Field, Requested(forValidate: true)]
    public string $description = "";
}
