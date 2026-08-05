<?php
namespace Framework\Core\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Migrations Model
 */
#[Model(
    hasTimestamps: true,
    canCreate:     true,
)]
class MigrationsModel {

    #[Field(isPrimary: true)]
    public string $name = "";

    #[Field]
    public string $title = "";
}
