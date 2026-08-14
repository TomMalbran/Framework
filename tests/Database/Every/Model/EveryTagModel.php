<?php
namespace Tests\Database\Every\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Database\Status\State;
use Framework\Database\Status\StateColor;
use Framework\Database\Status\Status;
use Framework\Utils\JSON;

use Tests\Database\Every\EveryKind;

/**
 * A Tag, which the other Models point at
 *
 * It is what the list a Field belongs to is checked against, what a Count
 * counts and what a Relation joins, and its Status carries States of its own
 * so the generated Status classes have something to write.
 */
#[Model(
    description:   "A tag kept for the tests.",
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
    canDelete:     true,
)]
class EveryTagModel {

    #[Field(isID: true)]
    public int $everyTagID = 0;

    #[Field, Requested]
    public string $name = "";

    #[Field]
    public JSON $settings;

    #[Field]
    public EveryKind $kind = EveryKind::None;

    #[Field(isFile: true)]
    public string $icon = "";

    #[Field]
    #[State("Draft", StateColor::Yellow)]
    #[State("Published", StateColor::Green)]
    #[State("Archived", StateColor::Red, isHidden: true)]
    public Status $status = Status::None;
}
