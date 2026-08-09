<?php
namespace Tests\Analysis\Fixture\Database;

use Framework\Database\Model\Field;
use Framework\Database\Model\Model;

#[Model(description: "A model that marks two fields as the id")]
class DuplicateIDModel {

    #[Field(isID: true)]
    public int $boxID;

    #[Field(isID: true)]
    public int $otherID;

    #[Field]
    public string $name;
}
