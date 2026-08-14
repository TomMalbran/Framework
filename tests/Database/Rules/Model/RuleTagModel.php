<?php
namespace Tests\Database\Rules\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

/**
 * The rows a belongsTo is checked against
 */
#[Model(
    description: "A tag a rule can point at, kept for the tests.",
    canCreate:   true,
    canEdit:     true,
)]
class RuleTagModel {

    #[Field(isID: true)]
    public int $ruleTagID = 0;

    #[Field, Requested]
    public string $name = "";
}
