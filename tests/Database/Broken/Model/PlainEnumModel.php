<?php
namespace Tests\Database\Broken\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

use Tests\Database\Broken\PlainKind;

/**
 * A Model typed by an Enum that is not one of the Framework
 */
#[Model(canCreate: true)]
class PlainEnumModel {

    #[Field(isID: true)]
    public int $plainEnumID = 0;

    #[Field]
    public PlainKind $kind = PlainKind::None;
}
