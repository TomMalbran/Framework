<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\Model;

#[Model(description: "A model with the one id it is allowed")]
class SingleIDModel {

    #[Field(isID: true)]
    public int $boxID;

    #[Field]
    public string $name;
}
