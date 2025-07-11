<?php
namespace Framework\Database\Model;

use Framework\Database\Model\Field;
use Framework\Utils\Strings;

use Attribute;

/**
 * The SubRequest Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SubRequest {

    public string $schemaName = "";
    public string $idName     = "";
    public string $fieldName  = "";
    public string $valueName  = "";
    public string $query      = "";


    // Used internally when parsing the Model
    public string $name       = "";
    public string $type       = "";



    /**
     * The SubRequest Attribute
     * @param string $schemaName Optional.
     * @param string $idName     Optional.
     * @param string $fieldName  Optional.
     * @param string $valueName  Optional.
     * @param string $query      Optional.
     */
    public function __construct(
        string $schemaName = "",
        string $idName     = "",
        string $fieldName  = "",
        string $valueName  = "",
        string $query      = "",
    ) {
        $this->schemaName = $schemaName;
        $this->idName     = $idName;
        $this->fieldName  = $fieldName;
        $this->valueName  = $valueName;
        $this->query      = $query;
    }



    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $schemaName
     * @param string $type
     * @return SubRequest
     */
    public function setData(string $name, string $type, string $schemaName): SubRequest {
        $this->name = $name;

        if ($type !== "") {
            $this->type = $type;
        }
        if ($schemaName !== "") {
            $this->schemaName = $schemaName;
        }
        return $this;
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        $result = [
            "name" => $this->name,
        ];
        if ($this->type !== "") {
            $result["type"] = $this->type;
        }
        if ($this->idName !== "") {
            $result["idKey"]  = Field::generateName($this->idName);
            $result["idName"] = $this->idName;
        }
        if ($this->fieldName !== "") {
            $result["field"] = $this->fieldName;
        }
        if ($this->valueName !== "") {
            $result["value"] = $this->valueName;
        }
        if ($this->query !== "") {
            $result["where"] = Strings::split($this->query, " ");
        }
        return $result;
    }
}
