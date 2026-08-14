<?php
namespace Tests\Database\Broken\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

/**
 * A Model whose own attribute cannot be built
 *
 * It is given an argument the attribute does not take, which PHP only finds
 * out when the attribute is asked for, so the Model is named as an error
 * rather than parsed.
 */
#[Model(isWonderful: true)]
class BrokenModelModel {

    #[Field(isID: true)]
    public int $brokenModelID = 0;
}
