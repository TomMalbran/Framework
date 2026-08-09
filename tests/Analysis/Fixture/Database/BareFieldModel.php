<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\Model;

#[Model(description: "A model with a property nobody described")]
class BareFieldModel {

    #[Field(isID: true)]
    public int $boxID;

    public string $undescribed;

    #[Field]
    public string $name;
}
