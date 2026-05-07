<?php
namespace Framework\Database\Model;

use Framework\Database\Model\Field;
use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;
use Framework\Utils\Color;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Validate Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate {

    // A basic condition used to apply the validation.
    // It can be in the form of "field = value" or "field != value".
    // Where "field" is a value from the Request.
    public string $if = "";

    // Indicates that the field is required.
    public bool $isRequired = false;

    // The field should be validated as an Email.
    public bool $isEmail = false;

    // The field should be validated as a URL.
    public bool $isUrl = false;

    // The field should be validated as a Number.
    // NOTE: Is only required when the type of the field is a String.
    public bool $isNumeric = false;

    // The field should be validated as a Signed Number.
    public bool $isSigned = false;

    // The field should be validated as a Price.
    public bool $isPrice = false;


    // Indicates the class name of the type of the field.
    public string $typeOf = "";

    // Indicates the class name of the module the field belongs to.
    public string $belongsTo = "";

    // The method used for validation in the typeOf or belongsTo class.
    // NOTE: By default it will use "isValid" for typeOf and "exists" for belongsTo.
    public string $method = "";

    // Adds the parent of the Model to the validation of the belongsTo.
    public bool $withParent = false;


    // The maximum length of a String field.
    public int $maxLength = 0;

    // The minimum value of a Number field.
    public int $minValue = 0;

    // The maximum value of a Number field.
    public int $maxValue = 0;

    // A property indicating that this value should be greater than another field.
    public string $greaterThan = "";


    // Used to use a different prefix for error messages.
    // NOTE: The default is to use the name of the Model in uppercase.
    public string $prefix = "";

    // Used as the prefix of the belongsTo error messages.
    // NOTE: The default is to use the name of the belongsTo Model in uppercase.
    public string $belongsName = "";



    /**
     * The Validate Attribute
     * @param string            $if          Optional.
     * @param bool              $isRequired  Optional.
     * @param bool              $isEmail     Optional.
     * @param bool              $isUrl       Optional.
     * @param bool              $isNumeric   Optional.
     * @param bool              $isSigned    Optional.
     * @param bool              $isPrice     Optional.
     * @param class-string|null $typeOf      Optional.
     * @param class-string|null $belongsTo   Optional.
     * @param string            $method      Optional.
     * @param bool              $withParent  Optional.
     * @param int               $maxLength   Optional.
     * @param int               $minValue    Optional.
     * @param int               $maxValue    Optional.
     * @param string            $greaterThan Optional.
     * @param string            $prefix      Optional.
     * @param string            $belongsName Optional.
     */
    public function __construct(
        string $if = "",
        bool $isRequired = false,
        bool $isEmail = false,
        bool $isUrl = false,
        bool $isNumeric = false,
        bool $isSigned = false,
        bool $isPrice = false,
        ?string $typeOf = null,
        ?string $belongsTo = null,
        string $method = "",
        bool $withParent = false,
        int $maxLength = 0,
        int $minValue = 0,
        int $maxValue = 0,
        string $greaterThan = "",
        string $prefix = "",
        string $belongsName = "",
    ) {
        $this->if          = $if;
        $this->isRequired  = $isRequired;
        $this->isEmail     = $isEmail;
        $this->isUrl       = $isUrl;
        $this->isNumeric   = $isNumeric;
        $this->isSigned    = $isSigned;
        $this->isPrice     = $isPrice;

        $this->typeOf      = $typeOf ?? "";
        $this->belongsTo   = $belongsTo ?? "";
        $this->method      = $method;
        $this->withParent  = $withParent;

        $this->maxLength   = $maxLength;
        $this->minValue    = $minValue;
        $this->maxValue    = $maxValue;
        $this->greaterThan = $greaterThan;

        $this->prefix      = Strings::toConstantCase($prefix);
        $this->belongsName = Strings::toConstantCase($belongsName);

        if ($this->method === "") {
            if (!Arrays::isEmpty($this->typeOf)) {
                $this->method = "isValid";
            } elseif (!Arrays::isEmpty($this->belongsTo)) {
                $this->method = "exists";
            }
        }
    }



    // Data that comes from the Field attribute
    public ValidateType $type = ValidateType::None;
    public FieldType $fieldType = FieldType::None;

    public string $name      = "";
    public bool   $isUnique  = false;
    public string $dateInput = "";
    public string $hourInput = "";


    /**
     * Sets the Field associated with this Validate
     * @param Field  $field
     * @param string $fantasyName
     * @return Validate
     */
    public function setField(Field $field, string $fantasyName): Validate {
        $this->name      = $field->name;
        $this->isUnique  = $field->isUnique;
        $this->dateInput = $field->dateInput;
        $this->hourInput = $field->hourInput;

        $this->type      = $this->getType($field);
        $this->fieldType = $field->type;

        if ($this->prefix === "") {
            $this->prefix = Strings::toConstantCase($fantasyName);
        }

        if ($this->type === ValidateType::Enum) {
            $this->typeOf = $field->enumClass;
            $this->method = "isValid";
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
        if ($this->isPrice) {
            return ValidateType::Price;
        }
        if ($field->dateInput !== "") {
            return ValidateType::Date;
        }
        if ($this->isNumeric || $field->type === FieldType::Number || $field->type === FieldType::Float) {
            return ValidateType::Number;
        }
        if ($field->type === FieldType::Enum) {
            return ValidateType::Enum;
        }
        if ($field->type->isString()) {
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
        $fieldName = Strings::toConstantCase($fieldName);

        return "{$this->prefix}_ERROR_{$fieldName}";
    }

    /**
     * Gets the Type Invalid Error string for this Validate
     * @return string
     */
    public function getTypeInvalidError(): string {
        if ($this->typeOf === Color::class) {
            return "GENERAL_ERROR_COLOR";
        }

        $prefix = $this->getFieldError();
        return "{$prefix}_INVALID";
    }

    /**
     * Gets the Type Exists Error string for this Validate
     * @return string
     */
    public function getTypeExistsError(): string {
        $typeName = Strings::substringAfter($this->typeOf, "\\");
        $prefix   = Strings::toConstantCase($typeName);
        return "{$prefix}_ERROR_EXISTS";
    }

    /**
     * Gets the BelongsTo Error string for this Validate
     * @return string
     */
    public function getBelongsToError(): string {
        $prefix = $this->belongsName;
        if ($prefix === "") {
            $belongsName = Strings::substringAfter($this->belongsTo, "\\");
            $prefix      = Strings::toConstantCase($belongsName);
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

        if (count($result) === 0) {
            return "";
        }
        return ", " . Strings::join($result, ", ");
    }

    /**
     * Creates the If statement for this Validate
     * @return string
     */
    public function createCondition(): string {
        $parts = Strings::split($this->if, " ");
        if (count($parts) !== 3) {
            return "";
        }

        $field = $parts[0];
        $value = trim(Strings::replace($parts[2], [ "'", '"'], ""));
        $op    = match ($parts[1]) {
            "=", "==", "==="  => "===",
            "!=", "!==", "<>" => "!==",
            default => "=",
        };

        return "if (\$request->{$field}->get() {$op} \"{$value}\") {";
    }
}
