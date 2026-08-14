<?php
namespace Tests\Database\Every\Model;

use Framework\Database\Model\Count;
use Framework\Database\Model\Model;
use Framework\Database\Model\Expression;
use Framework\Database\Model\Field;
use Framework\Database\Model\Relation;
use Framework\Database\Model\Requested;
use Framework\Database\Model\SubRequest;
use Framework\Database\Model\Validate;
use Framework\Database\Model\Virtual;
use Framework\Database\Status\Status;
use Framework\Date\Date;
use Framework\Date\Type\DateType;
use Framework\File\File;
use Framework\Utils\JSON;

use Tests\Database\Every\EveryKind;

/**
 * A Model with one field of every kind there is
 *
 * The Framework ships Models enough to run itself, which leaves the halves of
 * the generators that write floats, files, dates with a period, lists and
 * enums with nothing to write. This is what gives them something.
 */
#[Model(
    description:   "A field of every kind, kept for the tests.",
    hasTimestamps: true,
    canCreate:     true,
    canEdit:       true,
    canDelete:     true,
)]
class EveryFieldModel {

    #[Field(isID: true)]
    public int $everyFieldID = 0;

    #[Field(isParent: true)]
    public int $everyTagID = 0;


    #[Field, Requested, Validate(isRequired: true, maxLength: 40)]
    public string $name = "";

    // A file named inside the text is replaced where it is written
    #[Field(isText: true, hasFile: true), Requested]
    public string $notes = "";

    #[Field(isLongText: true), Requested]
    public string $story = "";

    #[Field(decimals: 3), Requested, Validate(isNumeric: true, minValue: 0, maxValue: 100)]
    public float $weight = 0;

    #[Field(decimals: 2), Requested, Validate(isPrice: true, isRequired: true)]
    public float $price = 0;

    #[Field(isSigned: true), Requested, Validate(isNumeric: true, greaterThan: "weight")]
    public int $balance = 0;

    #[Field, Requested, Validate(isEmail: true, isRequired: true)]
    public string $email = "";

    #[Field, Requested, Validate(isUrl: true)]
    public string $website = "";

    #[Field(isFile: true), Requested]
    public string $picture = "";

    #[Field(dateInput: "fromDate", hourInput: "fromHour"), Requested, Validate(isRequired: true)]
    public int $fromTime = 0;

    #[Field(dateInput: "toDate", hourInput: "toHour"), Requested, Validate]
    public int $toTime = 0;

    #[Field, Requested, Validate(isRequired: true)]
    public EveryKind $kind = EveryKind::None;

    #[Field, Requested(isJSON: true), Validate(belongsTo: EveryTagModel::class)]
    public JSON $tagIDs;

    #[Field, Requested]
    public bool $isPinned = false;

    // The files are kept as a plain array of paths
    #[Field(jsonFiles: true, hasFile: true), Requested(isJSON: true)]
    public JSON $files;

    #[Field]
    public ?Date $dueTime = null;

    #[Field, Requested, Validate]
    public Status $status = Status::None;


    #[Expression("(SELECT COUNT(*) FROM every_tag)")]
    public int $tagTotal = 0;

    #[Count(modelName: EveryTagModel::class, fieldName: "everyTagID")]
    public int $tagCount = 0;

    // The Model it joins carries a Status, which the Entity takes as one
    #[Relation(fieldNames: [ "name", "status", "settings", "kind", "icon" ])]
    public EveryTagModel $everyTag;

    /** @var list<EveryTagModel> */
    #[SubRequest]
    public array $tags = [];

    // One of a type that is not a Model, which is kept as it is written
    /** @var list<EveryKind> */
    #[SubRequest]
    public array $kindList = [];


    /** @var list<string> */
    #[Virtual]
    public array $tagNames = [];

    #[Virtual]
    public string $pictureName = "";

    #[Virtual]
    public ?Date $seenTime = null;

    #[Virtual]
    public JSON $meta;

    // With nothing said about what it holds, there is no type to write
    #[Virtual]
    public array $whatever = [];

    /** @var list<EveryKind> */
    #[Virtual]
    public array $seenKinds = [];


    #[Requested(isFile: true)]
    public ?File $upload = null;

    #[Requested(isString: true)]
    public int $searchID = 0;

    #[Requested(isNumber: true)]
    public string $quantity = "";

    #[Requested(isDate: true, dateInput: "onDate", hourInput: "onHour", dateType: DateType::End)]
    public int $onTime = 0;

    #[Requested]
    public EveryKind $wanted = EveryKind::None;

    #[Requested]
    public bool $isWanted = false;

    #[Requested]
    public float $ratio = 0;

    #[Requested]
    public int $tally = 0;

    /** @var list<EveryTagModel> */
    #[Requested]
    public array $tagList = [];

    /** @var list<EveryKind> */
    #[Requested]
    public array $kinds = [];

    /** @var array<string,string> */
    #[Requested]
    public array $labels = [];

    /** @var array<int,int> */
    #[Requested]
    public array $sizes = [];

    /** @var array<int,string> */
    #[Requested]
    public array $titles = [];

    /** @var array<string,int> */
    #[Requested]
    public array $counts = [];

    /** @var array<string,float> */
    #[Requested]
    public array $rates = [];

    /** @var array<string,mixed> */
    #[Requested]
    public array $extras = [];

    // A map of no shape the request knows how to read, which is left out
    /** @var array<int,float> */
    #[Requested]
    public array $ratios = [];

    // One requested with no date type of its own
    #[Requested(isDate: true, dateInput: "atDate")]
    public int $atTime = 0;

    #[Requested]
    public JSON $payload;
}
