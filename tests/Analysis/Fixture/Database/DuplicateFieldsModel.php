<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\Model;
use Framework\Database\Model\Validate;
use Framework\Database\Status\Status;

#[Model(description: "A model claiming each unique field twice over")]
class DuplicateFieldsModel {

    #[Field(isID: true)]
    public int $crateID;

    #[Field(isID: true)]
    public int $otherID;

    #[Field(isPosition: true)]
    public int $position;

    #[Field(isPosition: true)]
    public int $otherPosition;

    #[Field]
    public Status $status;

    #[Field]
    public Status|null $otherStatus;

    /** A plain type rather than a class, so the type is an identifier */
    #[Field]
    public string $name;

    /** An attribute the rule is not looking for */
    #[Validate(isRequired: true)]
    public string $validated;

    /** A statement that is not a property at all */
    public function notAProperty(): int {
        return 1;
    }
}
