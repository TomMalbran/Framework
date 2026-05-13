<?php
namespace Framework\Database\Model;

use Framework\Date\Type\DateType;

use Attribute;

/**
 * The Requested Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Requested {

    public bool $canEdit   = true;
    public bool $isMultiID = false;
    public bool $isNative  = false;
    public bool $isJSON    = false;
    public bool $isDate    = false;
    public bool $isFile    = false;
    public bool $isHour    = false;


    /**
     * The Requested Attribute
     * @param bool $canEdit   Optional.
     * @param bool $isMultiID Optional.
     * @param bool $isNative  Optional.
     * @param bool $isJSON    Optional.
     * @param bool $isDate    Optional.
     * @param bool $isFile    Optional.
     * @param bool $isHour    Optional.
     */
    public function __construct(
        bool $canEdit = true,
        bool $isMultiID = false,
        bool $isNative = false,
        bool $isJSON = false,
        bool $isDate = false,
        bool $isFile = false,
        bool $isHour = false,
    ) {
        $this->canEdit   = $canEdit;
        $this->isMultiID = $isMultiID;
        $this->isNative  = $isNative;
        $this->isJSON    = $isJSON;
        $this->isDate    = $isDate;
        $this->isFile    = $isFile;
        $this->isHour    = $isHour;
    }



    // Used internally when parsing the Model
    public string    $name = "";
    public FieldType $type = FieldType::String;

    public bool      $isField   = false;
    public bool      $hasValue  = false;
    public string    $enumClass = "";
    public DateType  $dateType  = DateType::None;
    public string    $dateInput = "";
    public string    $hourInput = "";
    public int       $decimals  = 0;


    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @param bool   $isEnum
     * @return Requested
     */
    public function setData(
        string $name,
        string $typeName,
        bool $isEnum,
    ): Requested {
        $this->name = $name;

        if ($this->isJSON) {
            $this->type = FieldType::JSON;
        } elseif ($this->isDate) {
            $this->type     = FieldType::Date;
            $this->hasValue = true;
        } elseif ($this->isFile) {
            $this->type     = FieldType::File;
            $this->hasValue = true;
        } elseif ($this->isHour) {
            $this->hasValue = true;
        } elseif ($isEnum) {
            $this->type      = FieldType::Enum;
            $this->enumClass = $typeName;
            $this->isNative  = true;
        } else {
            $this->type = FieldType::fromType($typeName);

            if (!$this->isNative && $this->type !== FieldType::JSON &&
                $this->type !== FieldType::Array
            ) {
                $this->hasValue = true;
            }
        }
        return $this;
    }

    /**
     * Sets the data from a Main Field
     * @param Field $field
     * @param bool  $isValidated
     * @return Requested
     */
    public function fromField(Field $field, bool $isValidated): Requested {
        $this->name = $field->name;
        $this->type = $field->type;
        if ($this->isFile) {
            $this->type = FieldType::File;
        }

        $this->enumClass = $field->enumClass;
        $this->dateType  = $field->dateType;
        $this->dateInput = $field->dateInput;
        $this->hourInput = $field->hourInput;
        $this->decimals  = $field->decimals;

        if (!$this->isNative) {
            $this->isNative = $field->isParent && !$isValidated;
        }

        if (!$field->isID && !$field->isParent) {
            $this->isField = $this->canEdit;
            if (!$this->isNative && $this->type !== FieldType::JSON) {
                $this->hasValue = true;
            }
        }
        return $this;
    }

    /**
     * Sets the data from a SubRequest
     * @param SubRequest $subRequest
     * @return Requested
     */
    public function fromSubRequest(SubRequest $subRequest): Requested {
        $this->name = $subRequest->name;
        $this->type = FieldType::JSON;
        return $this;
    }

    /**
     * Sets the data from a Status Field
     * @return Requested
     */
    public function fromStatus(): Requested {
        $this->name     = "status";
        $this->type     = FieldType::Enum;
        $this->isField  = $this->canEdit;
        $this->hasValue = true;
        return $this;
    }

    /**
     * Sets the data from a Position Field
     * @return Requested
     */
    public function fromPosition(): Requested {
        $this->name     = "position";
        $this->type     = FieldType::Number;
        $this->isField  = true;
        $this->hasValue = true;
        return $this;
    }



    /**
     * Returns the Value Type for a Field Type
     * @return string
     */
    public function getValueType(): string {
        if ($this->isHour) {
            return "HourValue";
        }

        return match ($this->type) {
            FieldType::None    => "",

            FieldType::Date    => "DateValue",
            FieldType::Enum    => "EnumValue",
            FieldType::JSON,
            FieldType::Array   => "StringValue",

            FieldType::Boolean => "BoolValue",
            FieldType::Number  => "NumberValue",
            FieldType::Float   => "FloatValue",

            FieldType::String,
            FieldType::Text,
            FieldType::LongText,
            FieldType::Encrypt => "StringValue",
            FieldType::File    => "FileValue",
        };
    }

    /**
     * Returns true if the Requested Field is a Dictionary
     * @return bool
     */
    public function isDictionary(): bool {
        return !$this->isNative && (
            $this->type === FieldType::JSON ||
            $this->type === FieldType::Array
        );
    }
}
