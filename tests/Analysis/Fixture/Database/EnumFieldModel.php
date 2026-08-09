<?php
namespace Tests\Analysis\Fixture\Database;

use Tests\Analysis\Fixture\Enum\Grade;
use Tests\Analysis\Fixture\Enum\Weekday;
use Framework\Database\Model\Field;
use Framework\Database\Model\Model;

#[Model(description: "A model holding both a proper enum field and a plain one")]
class EnumFieldModel {

    #[Field(isID: true)]
    public int $boxID;

    #[Field]
    public Weekday $reported;

    #[Field]
    public Grade $allowed;
}
