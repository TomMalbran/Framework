<?php
namespace Framework\Database\Model;

use Framework\Database\Type\RequestedType;
use Framework\Date\Type\DateType;

use Attribute;

/**
 * The Requested Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Requested {

    public bool $canEdit   = true;
    public bool $isID      = false;
    public bool $isMultiID = false;

    // Set the Value Type different from the Field Type
    public bool $isString  = false;
    public bool $isNumber  = false;
    public bool $isJSON    = false;
    public bool $isFile    = false;
    public bool $isDate    = false;

    // Special date options
    public DateType $dateType    = DateType::None;
    public string   $dateInput   = "";
    public string   $hourInput   = "";
    public bool     $useTimeZone = true;


    /**
     * The Requested Attribute
     * @param bool     $canEdit     Optional.
     * @param bool     $isID        Optional.
     * @param bool     $isMultiID   Optional.
     * @param bool     $isString    Optional.
     * @param bool     $isNumber    Optional.
     * @param bool     $isJSON      Optional.
     * @param bool     $isFile      Optional.
     * @param bool     $isDate      Optional.
     * @param DateType $dateType    Optional.
     * @param string   $dateInput   Optional.
     * @param string   $hourInput   Optional.
     * @param bool     $useTimeZone Optional.
     */
    public function __construct(
        bool $canEdit = true,
        bool $isID = false,
        bool $isMultiID = false,
        bool $isString = false,
        bool $isNumber = false,
        bool $isJSON = false,
        bool $isFile = false,
        bool $isDate = false,
        DateType $dateType = DateType::None,
        string $dateInput = "",
        string $hourInput = "",
        bool $useTimeZone = true,
    ) {
        $this->canEdit     = $canEdit;
        $this->isID        = $isID;
        $this->isMultiID   = $isMultiID;

        $this->isString    = $isString;
        $this->isNumber    = $isNumber;
        $this->isJSON      = $isJSON;
        $this->isFile      = $isFile;
        $this->isDate      = $isDate;

        $this->dateType    = $dateType;
        $this->dateInput   = $dateInput;
        $this->hourInput   = $hourInput;
        $this->useTimeZone = $useTimeZone;
    }



    // Used internally when parsing the Model
    public RequestedType $type = RequestedType::String;

    public string $name      = "";
    public bool   $isField   = false;
    public string $subType   = "";
    public string $subClass  = "";
    public string $enumClass = "";
    public int    $decimals  = 0;


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
        $this->setType(
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
        return $this;
    }

    /**
     * Sets the data from a Main Field
     * @param Field $field
     * @param bool  $isValidated
     * @return Requested
     */
    public function fromField(Field $field, bool $isValidated): Requested {
        $this->setType(fieldType: $field->type);
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
        return $this;
    }

    /**
     * Sets the data from a Status Field
     * @return Requested
     */
    public function fromStatus(): Requested {
        $this->name    = "status";
        $this->type    = RequestedType::Status;
        $this->isField = $this->canEdit;
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
    private function setType(
        ?FieldType $fieldType = null,
        string $typeName = "",
        string $subModelName = "",
        bool $isEnum = false,
    ): void {
        if ($this->isString) {
            $this->type = RequestedType::String;
        } elseif ($this->isNumber) {
            $this->type = RequestedType::Number;
        } elseif ($this->isJSON) {
            $this->type = RequestedType::Dictionary;
        } elseif ($this->isDate) {
            $this->type = RequestedType::Date;
        } elseif ($this->isFile) {
            $this->type = RequestedType::File;
        } elseif ($subModelName !== "") {
            $this->type = RequestedType::Dictionary;
        } elseif ($isEnum) {
            $this->type = RequestedType::Enum;
        } elseif ($fieldType !== null) {
            $this->type = RequestedType::fromField($fieldType);
        } elseif ($typeName !== "") {
            $this->type = RequestedType::fromType($typeName);
        }
    }
}
