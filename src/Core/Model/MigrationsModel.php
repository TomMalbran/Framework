<?php
namespace Framework\Core\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Migrations Model
 */
#[Model(
    description:   "The data migrations that already ran, so each one is applied once.",
    hasTimestamps: true,
    canCreate:     true,
)]
class MigrationsModel {

    #[Field(isPrimary: true)]
    public string $name = "";

    #[Field]
    public string $title = "";
}
