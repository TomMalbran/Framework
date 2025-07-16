<?php
namespace Framework\Database;

use Framework\Database\SchemaFactory;
use Framework\Database\SchemaModel;
use Framework\Database\Field;
use Framework\Database\Join;
use Framework\Database\Model\FieldType;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Schema Structure
 */
class Structure {

    public string $schema      = "";

    public string $table       = "";
    public string $idKey       = "";
    public string $idName      = "";

    /** @var Field[] */
    public array $fields       = [];

    /** @var Join[] */
    public array $joins        = [];

    /** @var SubRequest[] */
    public array $subRequests  = [];

    public bool $hasStatus     = false;
    public bool $hasPositions  = false;
    public bool $hasTimestamps = false;
    public bool $hasUsers      = false;
    public bool $canCreate     = false;
    public bool $canEdit       = false;
    public bool $canDelete     = false;


    /**
     * Creates a new Structure instance
     * @param string     $schema
     * @param Dictionary $data
     */
    public function __construct(string $schema, Dictionary $data) {
        $this->schema        = $schema;
        $this->table         = SchemaModel::getDbTableName($schema);
        $this->hasStatus     = $data->hasValue("hasStatus");
        $this->hasPositions  = $data->hasValue("hasPositions");
        $this->hasTimestamps = $data->hasValue("hasTimestamps");
        $this->hasUsers      = $data->hasValue("hasUsers");
        $this->canCreate     = $data->hasValue("canCreate");
        $this->canEdit       = $data->hasValue("canEdit");
        $this->canDelete     = $data->hasValue("canDelete");

        // Add additional Fields
        $fields = $data->getDict("fields");
        if ($this->hasStatus) {
            $fields->set("status", [
                "type"    => FieldType::String->name,
                "noEmpty" => true,
                "isKey"   => true,
                "default" => "",
            ]);
        }
        if ($this->hasPositions) {
            $fields->set("position", [
                "type"    => FieldType::Number->name,
                "default" => 0,
            ]);
        }
        if ($this->canCreate && $this->hasTimestamps) {
            $fields->set("createdTime", [
                "type"     => FieldType::Number->name,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canCreate && $this->hasUsers) {
            $fields->set("createdUser", [
                "type"     => FieldType::Number->name,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canEdit && $this->hasTimestamps) {
            $fields->set("modifiedTime", [
                "type"     => FieldType::Number->name,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canEdit && $this->hasUsers) {
            $fields->set("modifiedUser", [
                "type"     => FieldType::Number->name,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canDelete) {
            $fields->set("isDeleted", [
                "type"     => FieldType::Boolean->name,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }

        // Parse the Fields
        $idKey        = "";
        $primaryCount = 0;

        foreach ($fields as $key => $value) {
            if ($value->hasValue("isID")) {
                $idKey         = $key;
                $primaryCount += 1;
            } elseif ($value->hasValue("isPrimary")) {
                $primaryCount += 1;
            }
            if ($idKey === "" && $value->hasValue("isPrimary")) {
                $idKey = $key;
            }
        }
        if ($primaryCount > 1) {
            $idKey = "";
        }

        // Create the Fields
        foreach ($fields as $key => $value) {
            $field = new Field($key, $value);
            if ($key === $idKey) {
                $this->idKey  = $field->key;
                $this->idName = $field->name;
            }
            $this->fields[] = $field;
        }

        // Create the Joins
        foreach ($data->getDict("joins") as $key => $value) {
            $this->joins[] = new Join($key, $value);
        }

        // Create the SubRequests
        foreach ($data->getDict("subrequests") as $key => $value) {
            $subStructure = SchemaFactory::getStructure($key);
            $this->subRequests[] = new SubRequest($subStructure, $value, $this->idKey, $this->idName);
        }
    }



    /**
     * Returns the Key adding the table as the prefix
     * @param string $key
     * @return string
     */
    public function getKey(string $key): string {
        if (!Strings::contains($key, ".")) {
            $mainKey = $this->table;
            return "{$mainKey}.{$key}";
        }
        return $key;
    }
}
