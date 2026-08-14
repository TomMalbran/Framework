<?php
namespace Tests\Database\Rules\Model;

use Framework\Database\Model\Model;
use Framework\Database\Model\Field;
use Framework\Database\Model\Requested;
use Framework\Database\Model\Validate;
use Framework\Database\Status\Status;
use Framework\Date\Date;
use Framework\Utils\Color;
use Framework\Utils\JSON;

use Tests\Database\Rules\RuleKind;
use Tests\Database\Rules\RuleTags;

/**
 * One field per way of combining the Validate options
 *
 * The rules are written as a chain of if and elseif, so which one answers for
 * a value depends on the ones beside it: a required field that is also unique
 * checks the one before the other, and a number that belongs to a Model never
 * reaches the check on its range. Combining them is where it goes wrong, so
 * every combination is a field of its own here and the validation is run
 * rather than read.
 */
#[Model(
    description:   "A row per way of validating, kept for the tests.",
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
)]
class RuleModel {

    #[Field(isID: true)]
    public int $ruleID = 0;


    // The list is written before the unique on purpose: it loops over the
    // ids it holds, and the unique below needs the id of the row itself
    #[Field, Requested(isJSON: true), Validate(belongsTo: RuleTags::class)]
    public JSON $tagIDs;

    #[Field(isUnique: true), Requested, Validate(isRequired: true)]
    public string $name = "";


    #[Field, Requested, Validate(maxLength: 5)]
    public string $slug = "";

    #[Field, Requested, Validate(isRequired: true, maxLength: 8)]
    public string $title = "";

    #[Field, Requested, Validate(typeOf: RuleKind::class)]
    public string $kind = "";

    #[Field, Requested, Validate(isRequired: true, typeOf: RuleKind::class)]
    public string $flavor = "";


    #[Field, Requested, Validate(isNumeric: true, minValue: 1, maxValue: 10)]
    public int $amount = 0;

    #[Field, Requested, Validate(isRequired: true, isNumeric: true)]
    public int $total = 0;

    #[Field, Requested, Validate(belongsTo: RuleTags::class)]
    public int $ruleTagID = 0;

    #[Field, Requested, Validate(isRequired: true, belongsTo: RuleTags::class)]
    public int $otherTagID = 0;

    #[Field, Requested, Validate(isNumeric: true, greaterThan: "amount")]
    public int $bigger = 0;

    #[Field(isUnique: true), Requested, Validate(isNumeric: true, minValue: 1)]
    public int $serial = 0;

    // Unique with no floor under it, so an empty one reaches the check
    #[Field(isUnique: true), Requested, Validate]
    public int $ticket = 0;


    #[Field, Requested, Validate(isRequired: true, isEmail: true)]
    public string $email = "";

    #[Field(isUnique: true), Requested, Validate(isEmail: true)]
    public string $otherEmail = "";

    #[Field, Requested, Validate(isRequired: true, isUrl: true)]
    public string $website = "";

    #[Field(decimals: 2), Requested, Validate(isRequired: true, isPrice: true)]
    public float $price = 0;


    #[Field(dateInput: "fromDate", hourInput: "fromHour"), Requested, Validate(isRequired: true)]
    public ?Date $fromTime = null;

    #[Field(dateInput: "toDate"), Requested, Validate]
    public ?Date $toTime = null;


    // Only asked for when the kind says so
    #[Field, Requested, Validate(if: "kind = First", isRequired: true)]
    public string $note = "";

    // A float that is required, whose empty is 0.0 rather than 0
    #[Field(decimals: 2), Requested, Validate(if: "kind = First", isRequired: true, maxValue: 100)]
    public float $percent = 0;

    // A color, whose error is the shared key rather than the model's
    #[Field, Requested, Validate(typeOf: Color::class)]
    public string $color = "";

    #[Field, Requested]
    public Status $status = Status::None;
}
