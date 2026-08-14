<?php
namespace Tests\Database\Every\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

use Tests\Database\Every\EveryKind;

/**
 * A Model keyed by an Enum rather than by a number
 *
 * The rows are the cases of the Enum, which is what a Model of contents per
 * code looks like, and it is the only shape that makes the generators write
 * an Enum as the ID.
 */
#[Model(
    description: "A row per code, kept for the tests.",
    canCreate:   true,
    canEdit:     true,
)]
class EveryCodeModel {

    #[Field(isID: true, notAutoInc: true)]
    public EveryKind $everyCode = EveryKind::None;

    #[Field, Requested]
    public string $title = "";
}
