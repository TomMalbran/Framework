<?php
namespace Tests\Database\Every\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;

/**
 * A Model that joins two others, with a key made of both
 *
 * It has no ID of its own, so what the request is keyed by is the field that
 * says it is the one rather than the ID of the Model.
 */
#[Model(
    description: "A link between a field and a tag, kept for the tests.",
    canCreate:   true,
    canEdit:     true,
)]
class EveryLinkModel {

    #[Field(isPrimary: true)]
    public int $everyFieldID = 0;

    #[Field(isPrimary: true)]
    public int $everyTagID = 0;

    #[Requested(isID: true)]
    public string $linkCode = "";

    #[Field, Requested]
    public string $note = "";
}
