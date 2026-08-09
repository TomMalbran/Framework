<?php
namespace Framework\Email\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

/**
 * The Email White List Model
 */
#[Model(
    description:   "The addresses still written to while the app is not in production.",
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class EmailWhiteListModel {

    #[Field(isID: true)]
    public int $emailID = 0;

    #[Field(isUnique: true), Requested]
    public string $email = "";

    #[Field, Requested]
    public string $description = "";
}
