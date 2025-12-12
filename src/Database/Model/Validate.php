<?php
namespace Framework\Database\Model;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Validate Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate {

    // Indicates that the field is required.
    public bool   $isRequired  = false;

    // The field should be validated as an Email.
    public bool   $isEmail     = false;

    // The field should be validated as a URL.
    public bool   $isUrl       = false;

    // The field should be validated as a Color.
    public bool   $isColor     = false;

    // The field should be validated as a Number.
    // NOTE: Is only required when the type of the field is a String.
    public bool   $isNumeric   = false;

    // The field should be validated as a Signed Number.
    public bool   $isSigned    = false;

    // The field should be validated as a Price.
    public bool   $isPrice     = false;


    // Indicates the class name of the type of the field.
    public string $typeOf      = "";

    // Indicates the class name of the module the field belongs to.
    public string $belongsTo   = "";

    // The method used for validation in the typeOf or belongsTo class.
    // NOTE: By default it will use "isValid" for typeOf and "exists" for belongsTo.
    public string $method      = "";

    // Adds the parent of the Model to the validation of the belongsTo.
    public bool   $withParent  = false;


    // The maximum length of a String field.
    public int    $maxLength   = 0;

    // The minimum value of a Number field.
    public int    $minValue    = 0;

    // The maximum value of a Number field.
    public int    $maxValue    = 0;


    // Used to use a different prefix for error messages.
    // NOTE: The default is to use the name of the Model in uppercase.
    public string $prefix      = "";

    // Used as the prefix of the belongsTo error messages.
    // NOTE: The default is to use the name of the belongsTo Model in uppercase.
    public string $belongsName = "";



    /**
     * The Validate Attribute
     * @phpstan-param class-string $typeOf
     * @phpstan-param class-string $belongsTo
     *
     * @param boolean $isRequired  Optional.
     * @param boolean $isEmail     Optional.
     * @param boolean $isUrl       Optional.
     * @param boolean $isColor     Optional.
     * @param boolean $isNumeric   Optional.
     * @param boolean $isSigned    Optional.
     * @param boolean $isPrice     Optional.
     * @param string  $typeOf      Optional.
     * @param string  $belongsTo   Optional.
     * @param string  $method      Optional.
     * @param boolean $withParent  Optional.
     * @param integer $maxLength   Optional.
     * @param integer $minValue    Optional.
     * @param integer $maxValue    Optional.
     * @param string  $prefix      Optional.
     * @param string  $belongsName Optional.
     */
    public function __construct(
        bool   $isRequired  = false,
        bool   $isEmail     = false,
        bool   $isUrl       = false,
        bool   $isColor     = false,
        bool   $isNumeric   = false,
        bool   $isSigned    = false,
        bool   $isPrice     = false,

        string $typeOf      = "",
        string $belongsTo   = "",
        string $method      = "",
        bool   $withParent  = false,

        int    $maxLength   = 0,
        int    $minValue    = 0,
        int    $maxValue    = 0,

        string $prefix      = "",
        string $belongsName = "",
    ) {
        $this->isRequired  = $isRequired;
        $this->isEmail     = $isEmail;
        $this->isUrl       = $isUrl;
        $this->isColor     = $isColor;
        $this->isNumeric   = $isNumeric;
        $this->isSigned    = $isSigned;
        $this->isPrice     = $isPrice;

        $this->typeOf      = $typeOf;
        $this->belongsTo   = $belongsTo;
        $this->method      = $method;
        $this->withParent  = $withParent;

        $this->maxLength   = $maxLength;
        $this->minValue    = $minValue;
        $this->maxValue    = $maxValue;

        $this->prefix      = Strings::toUpperCase($prefix);
        $this->belongsName = Strings::toUpperCase($belongsName);

        if ($this->method === "" && $this->typeOf !== "") {
            $this->method = "isValid";
        } elseif ($this->method === "" && $this->belongsTo !== "") {
            $this->method = "exists";
        }
    }



    // Data that comes from the Field attribute
    public ValidateType $type = ValidateType::None;

    public string $name      = "";
    public bool   $isUnique  = false;
    public string $dateInput = "";
    public string $hourInput = "";
    public int    $decimals  = 0;


    /**
     * Sets the Field associated with this Validate
     * @param Field  $field
     * @param string $fantasyName
     * @return Validate
     */
    public function setField(Field $field, string $fantasyName): Validate {
        $this->name        = $field->name;
        $this->isUnique    = $field->isUnique;
        $this->dateInput   = $field->dateInput;
        $this->hourInput   = $field->hourInput;
        $this->decimals    = $field->decimals;

        $this->type        = $this->getType($field);

        if ($this->prefix === "") {
            $this->prefix = Strings::pascalCaseToUpperCase($fantasyName);
        }
        return $this;
    }

    /**
     * Sets the Validate Type to Status
     * @return Validate
     */
    public function setStatus(): Validate {
        $this->name = "status";
        $this->type = ValidateType::Status;
        return $this;
    }

    /**
     * Gets the Validate Type from the Field
     * @param Field $field
     * @return ValidateType
     */
    private function getType(Field $field): ValidateType {
        if ($this->isEmail) {
            return ValidateType::Email;
        }
        if ($this->isUrl) {
            return ValidateType::Url;
        }
        if ($this->isColor) {
            return ValidateType::Color;
        }
        if ($this->isPrice) {
            return ValidateType::Price;
        }
        if ($field->dateInput !== "") {
            return ValidateType::Date;
        }
        if ($this->isNumeric || $field->type === FieldType::Number || $field->type === FieldType::Float) {
            return ValidateType::Number;
        }
        if (FieldType::isString($field->type)) {
            return ValidateType::String;
        }
        return ValidateType::None;
    }



    /**
     * Gets the Field Error for this Validate
     * @return string
     */
    public function getFieldError(): string {
        $fieldName = trim(ucfirst($this->name));
        $fieldName = Strings::stripEnd($fieldName, "ID");
        $fieldName = Strings::PascalCaseToUpperCase($fieldName);

        return "{$this->prefix}_ERROR_{$fieldName}";
    }

    /**
     * Gets the Type Error string for this Validate
     * @return string
     */
    public function getTypeError(): string {
        $typeName = Strings::substringAfter($this->typeOf, "\\");
        return Strings::PascalCaseToUpperCase($typeName);
    }

    /**
     * Gets the BelongsTo Error string for this Validate
     * @return string
     */
    public function getBelongsToError(): string {
        $prefix = $this->belongsName;
        if ($prefix === "") {
            $belongsName = Strings::substringAfter($this->belongsTo, "\\");
            $prefix      = Strings::PascalCaseToUpperCase($belongsName);
        }
        return "{$prefix}_ERROR_EXISTS";
    }

    /**
     * Gets the parameters for the isNumeric validation
     * @return string
     */
    public function getNumericParams(): string {
        $result = [];

        if ($this->isSigned && $this->minValue === 0) {
            $result[] = "null";
        } elseif (!$this->isRequired || $this->minValue !== 0 || $this->maxValue !== 0) {
            $result[] = $this->minValue;
        }
        if ($this->maxValue !== 0) {
            $result[] = $this->maxValue;
        }

        if ($this->decimals !== 0) {
            for ($i = count($result); $i < 2; $i++) {
                $result[] = "null";
            }
            $result[] = $this->decimals;
        }

        if (count($result) === 0) {
            return "";
        }
        return ", " . Strings::join($result, ", ");
    }
}
