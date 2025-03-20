<?php
namespace Framework\Database;

use Framework\Database\Factory;
use Framework\Database\Field;
use Framework\Database\Join;
use Framework\Database\Count;
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
    public string $idType      = "";
    public string $nameKey     = "";

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
    public bool $hasFilters    = false;
    public bool $hasEncrypt    = false;
    public bool $canCreate     = false;
    public bool $canEdit       = false;
    public bool $canDelete     = false;
    public bool $canRemove     = false;


    /**
     * Creates a new Structure instance
     * @param string     $schema
     * @param Dictionary $data
     */
    public function __construct(string $schema, Dictionary $data) {
        $this->schema        = $schema;
        $this->table         = Factory::getTableName($schema);
        $this->hasStatus     = $data->hasValue("hasStatus");
        $this->hasPositions  = $data->hasValue("hasPositions");
        $this->hasTimestamps = $data->hasValue("hasTimestamps");
        $this->hasUsers      = $data->hasValue("hasUsers");
        $this->hasFilters    = $data->hasValue("hasFilters");
        $this->canCreate     = $data->hasValue("canCreate");
        $this->canEdit       = $data->hasValue("canEdit");
        $this->canDelete     = $data->hasValue("canDelete");
        $this->canRemove     = $data->hasValue("canRemove");

        // Add additional Fields
        $fields = $data->getDict("fields");
        if ($this->hasStatus) {
            $fields->set("status", [
                "type"    => Field::String,
                "noEmpty" => true,
                "default" => "",
            ]);
        }
        if ($this->hasPositions) {
            $fields->set("position", [
                "type"    => Field::Number,
                "default" => 0,
            ]);
        }
        if ($this->canCreate && $this->hasTimestamps) {
            $fields->set("createdTime", [
                "type"     => Field::Date,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canCreate && $this->hasUsers) {
            $fields->set("createdUser", [
                "type"     => Field::Number,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canEdit && $this->hasTimestamps) {
            $fields->set("modifiedTime", [
                "type"     => Field::Date,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canEdit && $this->hasUsers) {
            $fields->set("modifiedUser", [
                "type"     => Field::Number,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }
        if ($this->canDelete) {
            $fields->set("isDeleted", [
                "type"     => Field::Boolean,
                "cantEdit" => true,
                "default"  => 0,
            ]);
        }

        // Parse the Fields
        $idKey        = "";
        $primaryCount = 0;
        $reqMasterKey = false;

        foreach ($fields as $key => $value) {
            if ($value->getString("type") === Field::ID) {
                $idKey         = $key;
                $primaryCount += 1;
            } elseif ($value->hasValue("isPrimary")) {
                $primaryCount += 1;
            }
            if ($idKey === "" && $value->hasValue("isPrimary")) {
                $idKey = $key;
            }
            if ($value->getString("type") === Field::Encrypt) {
                $reqMasterKey = true;
            }
        }
        if ($primaryCount > 1) {
            $idKey = "";
        }

        // Create the Fields
        foreach ($fields as $key => $value) {
            $field = new Field($key, $value);
            if ($field->type === Field::ID) {
                $this->hasAutoInc = true;
            }
            if ($key == $idKey) {
                $this->hasID  = true;
                $this->idKey  = $field->key;
                $this->idName = $field->name;
                $this->idType = $field->type;
            }
            if ($field->isName) {
                $this->nameKey = $field->type === Field::Text ? "{$field->key}Short" : $field->key;
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
            $subStructure = Factory::getStructure($key);
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
     * Returns the Order Field
     * @param string|null $field Optional.
     * @return string
     */
    public function getOrder(?string $field = null): string {
        if (!empty($field)) {
            return $field;
        }
        return $this->hasPositions ? "position" : $this->nameKey;
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
