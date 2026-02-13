<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Virtual Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Virtual {

    // Used internally when parsing the Model
    public FieldType $type      = FieldType::String;
    public string    $name      = "";
    public string    $enumClass = "";


    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @param bool   $isEnum
     * @return Virtual
     */
    public function setData(string $name, string $typeName, bool $isEnum): Virtual {
        $this->name = $name;

        if ($isEnum) {
            $this->type      = FieldType::Enum;
            $this->enumClass = $typeName;
        } else {
            $this->type = FieldType::fromType($typeName);
        }
        return $this;
    }
}
