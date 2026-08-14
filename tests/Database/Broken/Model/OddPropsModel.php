<?php
namespace Tests\Database\Broken\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Status\Status;

use DateTime;

/**
 * A Model with the properties the parser walks past
 *
 * One has no type at all, one is a class the parser has no use for, and the
 * second Status is the one it already has.
 */
#[Model(canCreate: true)]
class OddPropsModel {

    #[Field(isID: true)]
    public int $oddPropsID = 0;

    #[Field]
    public Status $status = Status::None;

    #[Field]
    public Status $otherStatus = Status::None;

    #[Field]
    public ?DateTime $when = null;

    /** @phpstan-ignore-next-line */
    public $anything;
}
