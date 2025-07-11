<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Expression Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Expression {

    public string $expression = "";


    // Used internally when parsing the Model
    public FieldType $type    = FieldType::String;
    public string    $name    = "";


    /**
     * The Expression Attribute
     * @param string $expression
     */
    public function __construct(string $expression) {
        $this->expression = $expression;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Expression
     */
    public function setData(string $name, string $typeName): Expression {
        $this->name = $name;
        $this->type = FieldType::fromType($typeName);
        return $this;
    }

    /**
     * Returns the Data as an Array
     * @return array<string,string>
     */
    public function toArray(): array {
        return [
            "expression" => $this->expression,
            "type"       => $this->type->getName(),
        ];
    }
}
