<?php
namespace Framework\Database;

use Framework\Database\SchemaFactory;
use Framework\Database\Field;
use Framework\Database\Join;
use Framework\Database\Count;
use Framework\Database\Model\FieldType;
use Framework\Utils\Dictionary;
use Framework\Utils\Strings;

/**
 * The Schema Structure
 */
class Structure {

    public string $schema      = "";

    public string $table       = "";
    public bool   $hasID       = false;
    public bool   $hasAutoInc  = false;
    public string $idKey       = "";
    public string $idName      = "";

    public FieldType $idType   = FieldType::String;

    /** @var Field[] */
    public array $fields       = [];

    /** @var Field[] */
    public array $expressions  = [];

    /** @var Field[] */
    public array $processed    = [];

    /** @var Join[] */
    public array $joins        = [];

    /** @var Count[] */
    public array $counts       = [];

    /** @var SubRequest[] */
    public array $subRequests  = [];

    public bool $hasStatus     = false;
    public bool $hasPositions  = false;
    public bool $hasTimestamps = false;
    public bool $hasUsers      = false;
    public bool $hasEncrypt    = false;
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
        $this->table         = SchemaFactory::getTableName($schema);
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
        $reqMasterKey = false;

        foreach ($fields as $key => $value) {
            $type = FieldType::from($value->getString("type"));
            if ($type === FieldType::ID) {
                $idKey         = $key;
                $primaryCount += 1;
            } elseif ($value->hasValue("isPrimary")) {
                $primaryCount += 1;
            }
            if ($idKey === "" && $value->hasValue("isPrimary")) {
                $idKey = $key;
            }
            if ($type === FieldType::Encrypt) {
                $reqMasterKey = true;
            }
        }
        if ($primaryCount > 1) {
            $idKey = "";
        }

        // Create the Fields
        foreach ($fields as $key => $value) {
            $field = new Field($key, $value);
            if ($field->type === FieldType::ID) {
                $this->hasAutoInc = true;
            }
            if ($key === $idKey) {
                $this->hasID  = true;
                $this->idKey  = $field->key;
                $this->idName = $field->name;
                $this->idType = $field->type;
            }
            $this->fields[] = $field;
        }

        // Create the Expressions
        foreach ($data->getDict("expressions") as $key => $value) {
            $expression = $value->getString("expression");
            $this->expressions[$expression] = new Field($key, $value);
        }

        // Create the Processed
        foreach ($data->getDict("processed") as $key => $value) {
            $this->processed[] = new Field($key, $value);
        }

        // Create the Joins
        foreach ($data->getDict("joins") as $key => $value) {
            $this->joins[] = new Join($key, $value);
        }

        // Create the Counts
        foreach ($data->getDict("counts") as $key => $value) {
            $this->counts[] = new Count($key, $value);
        }

        // Create the SubRequests
        foreach ($data->getDict("subrequests") as $key => $value) {
            $subStructure = SchemaFactory::getStructure($key);
            $this->subRequests[] = new SubRequest($subStructure, $value, $this->idKey, $this->idName);
        }

        // Set the Master Key
        if ($reqMasterKey) {
            $this->hasEncrypt = true;
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

    /**
     * Replaces the Table in the Expression
     * @param string $expression
     * @return string
     */
    public function replaceTable(string $expression): string {
        return Strings::replace($expression, "{table}", "`{$this->table}`");
    }
}
