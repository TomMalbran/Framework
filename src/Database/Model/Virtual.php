<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Virtual Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Virtual {

    public bool $isJSON = false;


    // Used internally when parsing the Model
    public FieldType $type    = FieldType::String;
    public string    $name    = "";


    /**
     * The Virtual Attribute
     * @param bool $isJSON Optional.
     */
    public function __construct(bool $isJSON = false) {
        $this->isJSON = $isJSON;
    }

    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Virtual
     */
    public function setData(string $name, string $typeName): Virtual {
        $this->name = $name;

        if ($this->isJSON) {
            $this->type = FieldType::JSON;
        } else {
            $this->type = FieldType::fromType($typeName);
        }
        return $this;
    }

    /**
     * Returns the Data as an Array
     * @return array<string,string>
     */
    public function toArray(): array {
        return [
            "type" => $this->type->getName(),
        ];
    }
}
