<?php
namespace Framework\Database\Model;

use Framework\Database\Model\FieldType;
use Framework\Utils\Arrays;
use Framework\Utils\JSON;
use Framework\Utils\Numbers;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Expression Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Expression {

    public string $expression = "";


    /**
     * The Expression Attribute
     * @param string $expression
     */
    public function __construct(string $expression) {
        $this->expression = $expression;
    }



    // Used internally when parsing the Model
    public string    $name = "";
    public FieldType $type = FieldType::String;


    /**
     * Creates an Expression
     * @param string    $name
     * @param FieldType $type
     * @param string    $expression
     * @return Expression
     */
    public static function create(
        string    $name,
        FieldType $type,
        string    $expression,
    ): Expression {
        $result = new self($expression);
        $result->name = $name;
        $result->type = $type;
        return $result;
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
     * Returns the Field Values from the given Data
     * @param array<string,mixed> $data
     * @return array<string,string|integer|float|boolean|array<string|integer,mixed>>
     */
    public function toValues(array $data): array {
        $key    = $this->name;
        $text   = isset($data[$key]) ? Strings::toString($data[$key]) : "";
        $number = isset($data[$key]) ? Numbers::toInt($data[$key])    : 0;
        $result = [];

        $result[$key] = match ($this->type) {
            FieldType::Number  => $number,
            FieldType::Boolean => !Arrays::isEmpty($data, $key),
            FieldType::Float   => Numbers::toFloat($number, 2),
            FieldType::JSON    => JSON::decodeAsArray($text),
            default            => $text,
        };
        return $result;
    }

    /**
     * Returns the Data to build an Expression
     * @return array<string,mixed>
     */
    public function toBuildData(): array {
        return [
            "name"       => $this->name,
            "type"       => $this->type,
            "expression" => $this->expression,
        ];
    }
}
