<?php
namespace Tests\Database\Broken\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;

use Tests\Database\Broken\HalfKind;

/**
 * A Model typed by an Enum that cannot be written out as JSON
 */
#[Model(canCreate: true)]
class HalfEnumModel {

    #[Field(isID: true)]
    public int $halfEnumID = 0;

    #[Field]
    public HalfKind $kind = HalfKind::None;
}
