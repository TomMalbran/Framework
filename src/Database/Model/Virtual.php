<?php
namespace Framework\Database\Model;

use Attribute;

/**
 * The Virtual Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Virtual {

    // Used internally when parsing the Model
    public string    $name      = "";
    public FieldType $type      = FieldType::String;
    public string    $subType   = "";
    public string    $enumClass = "";


    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @param string $subType
     * @param bool   $isEnum
     * @return Virtual
     */
    public function setData(string $name, string $typeName, string $subType, bool $isEnum): Virtual {
        $this->name = $name;

        if ($isEnum) {
            $this->type      = FieldType::Enum;
            $this->enumClass = $typeName;
        } else {
            $this->type    = FieldType::fromType($typeName);
            $this->subType = $subType;
        }
        return $this;
    }
}
