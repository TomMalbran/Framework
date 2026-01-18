<?php
namespace Framework\Core\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * The Settings Model
 */
#[Model(
    hasTimestamps: true,
    canEdit:       true,
)]
class SettingsModel {

    #[Field(isPrimary: true)]
    public string $section = "";

    #[Field(isPrimary: true)]
    public string $variable = "";

    #[Field(isText: true)]
    public string $value = "";

    public string $variableType = "";
}
