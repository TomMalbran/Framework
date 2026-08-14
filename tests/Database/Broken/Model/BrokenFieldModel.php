<?php
namespace Tests\Database\Broken\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * A Model with a Field attribute that cannot be built
 */
#[Model(canCreate: true)]
class BrokenFieldModel {

    #[Field(isID: true)]
    public int $brokenFieldID = 0;

    #[Field(length: "wide")]
    public string $name = "";
}
