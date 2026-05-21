<?php
namespace Framework\Database\Model;

use Framework\Database\Type\ValueType;
use Framework\Date\Type\DateType;

use Attribute;

/**
 * The Requested Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Requested {

    public bool $canEdit     = true;
    public bool $isID        = false;
    public bool $isMultiID   = false;
    public bool $forValidate = false;

    public bool $isString    = false;
    public bool $isNumber    = false;
    public bool $isJSON      = false;
    public bool $isFile      = false;
    public bool $isDate      = false;
    public bool $isHour      = false;

    public DateType $dateType  = DateType::None;
    public string   $dateInput = "";
    public string   $hourInput = "";


    /**
     * The Requested Attribute
     * @param bool     $canEdit     Optional.
     * @param bool     $isID        Optional.
     * @param bool     $isMultiID   Optional.
     * @param bool     $forValidate Optional.
     * @param bool     $isString    Optional.
     * @param bool     $isNumber    Optional.
     * @param bool     $isJSON      Optional.
     * @param bool     $isFile      Optional.
     * @param bool     $isDate      Optional.
     * @param bool     $isHour      Optional.
     * @param DateType $dateType    Optional.
     * @param string   $dateInput   Optional.
     * @param string   $hourInput   Optional.
     */
    public function __construct(
        bool $canEdit = true,
        bool $isID = false,
        bool $isMultiID = false,
        bool $forValidate = false,
        bool $isString = false,
        bool $isNumber = false,
        bool $isJSON = false,
        bool $isFile = false,
        bool $isDate = false,
        bool $isHour = false,
        DateType $dateType = DateType::None,
        string $dateInput = "",
        string $hourInput = "",
    ) {
        $this->canEdit     = $canEdit;
        $this->isID        = $isID;
        $this->isMultiID   = $isMultiID;
        $this->forValidate = $forValidate;

        $this->isString    = $isString;
        $this->isNumber    = $isNumber;
        $this->isJSON      = $isJSON;
        $this->isFile      = $isFile;
        $this->isDate      = $isDate;
        $this->isHour      = $isHour;

        $this->dateType    = $dateType;
        $this->dateInput   = $dateInput;
        $this->hourInput   = $hourInput;
    }



    // Used internally when parsing the Model
    public string    $name = "";
    public ValueType $type = ValueType::String;

    public bool      $isField   = false;
    public string    $subType   = "";
    public string    $subClass  = "";
    public string    $enumClass = "";
    public int       $decimals  = 0;


    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @param string $subType
     * @param string $subClass
     * @param string $subModelName
     * @param bool   $isEnum
     * @return Requested
     */
    public function setData(
        string $name,
        string $typeName,
        string $subType,
        string $subClass,
        string $subModelName,
        bool $isEnum,
    ): Requested {
        $this->setValueType(
            typeName:     $typeName,
            subModelName: $subModelName,
            isEnum:       $isEnum,
        );
        $this->name     = $name;
        $this->subType  = $subType;
        $this->subClass = $subClass;

        if ($isEnum) {
            $this->enumClass = $typeName;
        }

        if ($this->type === ValueType::File) {
            $this->forValidate = true;
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
        $this->setValueType(fieldType: $field->type);
        $this->name = $field->name;

        $this->isID      = $field->isID;
        $this->enumClass = $field->enumClass;
        $this->dateType  = $field->dateType;
        $this->dateInput = $field->dateInput;
        $this->hourInput = $field->hourInput;
        $this->decimals  = $field->decimals;

        if (!$field->isID && !$field->isParent) {
            $this->isField = $this->canEdit;
        }
        if ($isValidated || $this->type === ValueType::File) {
            $this->forValidate = true;
        }
        return $this;
    }

    /**
     * Sets the data from a Status Field
     * @return Requested
     */
    public function fromStatus(): Requested {
        $this->name        = "status";
        $this->type        = ValueType::Enum;
        $this->isField     = $this->canEdit;
        $this->forValidate = true;
        return $this;
    }

    /**
     * Sets the data from a Position Field
     * @return Requested
     */
    public function fromPosition(): Requested {
        $this->name        = "position";
        $this->type        = ValueType::Number;
        $this->isField     = true;
        $this->forValidate = true;
        return $this;
    }



    /**
     * Sets the Value Type for a Field Type
     * @param FieldType|null $fieldType    Optional.
     * @param string         $typeName     Optional.
     * @param string         $subModelName Optional.
     * @param bool           $isEnum       Optional.
     * @return void
     */
    private function setValueType(
        ?FieldType $fieldType = null,
        string $typeName = "",
        string $subModelName = "",
        bool $isEnum = false,
    ): void {
        if ($this->isString) {
            $this->type = ValueType::String;
        } elseif ($this->isNumber) {
            $this->type = ValueType::Number;
        } elseif ($this->isJSON) {
            $this->type = ValueType::Dictionary;
        } elseif ($this->isDate) {
            $this->type = ValueType::Date;
        } elseif ($this->isFile) {
            $this->type = ValueType::File;
        } elseif ($this->isHour) {
            $this->type = ValueType::Hour;
        } elseif ($subModelName !== "") {
            $this->type = ValueType::Dictionary;
        } elseif ($isEnum) {
            $this->type = ValueType::Enum;
        } elseif ($fieldType !== null) {
            $this->type = ValueType::fromField($fieldType);
        } elseif ($typeName !== "") {
            $this->type = ValueType::fromType($typeName);
        }
    }

    /**
     * Returns the Value Type for a Field Type
     * @return string
     */
    public function getValueClass(): string {
        return match ($this->type) {
            ValueType::None       => "",

            ValueType::Enum       => "EnumValue",
            ValueType::Date       => "DateValue",
            ValueType::Hour       => "HourValue",
            ValueType::File       => "FileValue",

            ValueType::Boolean    => "BoolValue",
            ValueType::Number     => "NumberValue",
            ValueType::Float      => "FloatValue",

            ValueType::String,
            ValueType::Encrypt    => "StringValue",

            ValueType::Array,
            ValueType::Dictionary => "",
        };
    }

    /**
     * Returns true if the Requested Field is a Field
     * @return bool
     */
    public function isField(): bool {
        return !$this->forValidate &&
            !$this->isID &&
            $this->type !== ValueType::Dictionary;
    }

    /**
     * Returns true if the Requested Field is a Value
     * @return bool
     */
    public function isValue(): bool {
        return $this->forValidate &&
            !$this->isID &&
            $this->type !== ValueType::Dictionary;
    }

    /**
     * Returns true if the Requested Field is a Dictionary
     * @return bool
     */
    public function isDictionary(): bool {
        return ($this->forValidate && $this->type === ValueType::Array) ||
            $this->type === ValueType::Dictionary;
    }
}
