<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Requested Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Requested {

    public bool $isParent = false;
    public bool $isJSON   = false;


    /**
     * The Requested Attribute
     * @param bool $isParent Optional.
     * @param bool $isJSON   Optional.
     */
    public function __construct(
        bool $isParent = false,
        bool $isJSON = false,
    ) {
        $this->isParent = $isParent;
        $this->isJSON   = $isJSON;
    }


    // Internal data used when parsing the Model
    public string    $name = "";
    public FieldType $type = FieldType::String;

    public string    $dateInput = "";
    public string    $hourInput = "";
    public int       $decimals = 0;



    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Requested
     */
    public function setData(string $name, string $typeName): Requested {
        $this->name = $name;

        if ($this->isJSON) {
            $this->type = FieldType::JSON;
        } else {
            $this->type = FieldType::fromType($typeName);
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
        $this->name      = $field->name;
        $this->type      = $field->type;

        $this->dateInput = $field->dateInput;
        $this->hourInput = $field->hourInput;
        $this->decimals  = $field->decimals;

        if (!$this->isParent) {
            $this->isParent = $field->isParent && !$isValidated;
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
        $this->name = "status";
        $this->type = FieldType::String;
        return $this;
    }

    /**
     * Sets the data from a Position Field
     * @return Requested
     */
    public function fromPosition(): Requested {
        $this->name = "position";
        $this->type = FieldType::Number;
        return $this;
    }



    /**
     * Returns true if the Requested Field has a value
     * @return bool
     */
    public function hasValue(): bool {
        return $this->type !== FieldType::JSON && !$this->isParent;
    }

    /**
     * Returns true if the Requested Field is a Dictionary
     * @return bool
     */
    public function isDictionary(): bool {
        return $this->type === FieldType::JSON && !$this->isParent;
    }
}
