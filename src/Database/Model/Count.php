<?php
namespace Framework\Database\Model;

use Framework\Database\SchemaModel;
use Framework\Database\Model\FieldType;
use Framework\Utils\Strings;

use Attribute;

/**
 * The Count Attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Count {

    public string $schemaName = "";
    public string $onSchema   = "";
    public string $fieldName  = "";
    public string $query      = "";


    // Used internally when parsing the Model
    public ?SchemaModel $model = null;
    public FieldType    $type  = FieldType::String;
    public string       $name  = "";



    /**
     * The Count Attribute
     * @param string $schemaName Optional.
     * @param string $onSchema   Optional.
     * @param string $fieldName  Optional.
     * @param string $query      Optional.
     */
    public function __construct(
        string $schemaName = "",
        string $onSchema   = "",
        string $fieldName  = "",
        string $query      = "",
    ) {
        $this->schemaName = $schemaName;
        $this->onSchema   = $onSchema;
        $this->fieldName  = $fieldName;
        $this->query      = $query;
    }



    /**
     * Sets the Data from the Model
     * @param string $name
     * @param string $typeName
     * @return Count
     */
    public function setData(string $name, string $typeName): Count {
        $this->name = $name;
        $this->type = match ($typeName) {
            "bool"  => FieldType::Boolean,
            "float" => FieldType::Float,
            "int"   => FieldType::Number,
            default => FieldType::String,
        };
        return $this;
    }

    /**
     * Returns the Data as an Array
     * @return array<string,mixed>
     */
    public function toArray(): array {
        if ($this->model === null) {
            return [];
        }

        $key = $this->fieldName;
        if (Strings::endsWith($key, "ID")) {
            $key = Strings::replace($key, "ID", "Id");
            $key = Strings::camelCaseToUpperCase($key);
        }

        $result = [
            "isSum"     => false,
            "schema"    => $this->schemaName,
            "key"       => $key,
            "type"      => "number",
            "noDeleted" => $this->model->canDelete,
        ];
        if ($this->onSchema !== "") {
            $result["onSchema"] = $this->onSchema;
        }
        if ($this->query !== "") {
            $result["where"] = Strings::split($this->query, " ");
        }
        return $result;
    }
}
