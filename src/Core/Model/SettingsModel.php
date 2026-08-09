<?php
namespace Framework\Core\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Core\VariableType;

/**
 * The Settings Model
 */
#[Model(
    description:   "The application settings, one typed row per variable, grouped in sections.",
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

    #[Field]
    public VariableType $variableType = VariableType::None;
}
